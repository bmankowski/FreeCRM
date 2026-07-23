<?php
namespace App\Email;

use PHPMailer\PHPMailer\PHPMailer;

/**
 * Mailer basic class
 * @package YetiForce.App
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */
class Mailer
{

	/** SMTP connect/send timeout during configuration test (seconds) */
	private const SMTP_TEST_TIMEOUT = 15;

	/** @var string[] Queue status */
	public static $statuses = [
		0 => 'LBL_PENDING_ACCEPTANCE',
		1 => 'LBL_WAITING_TO_BE_SENT',
		2 => 'LBL_ERROR_DURING_SENDING',
	];
	public static $quoteJsonColumn = ['to', 'cc', 'bcc', 'attachments', 'params'];
	public static $quoteColumn = ['smtp_id', 'date', 'owner', 'status', 'from', 'subject', 'content', 'to', 'cc', 'bcc', 'attachments', 'priority', 'params', 'source_module', 'source_id'];

	/** @var PHPMailer PHPMailer instance */
	protected $mailer;

	/** @var array SMTP configuration */
	protected $smtp;

	/** @var array Error logs */
	protected $error;

	/**
	 * Construct
	 */
	public function __construct()
	{
		$this->mailer = new PHPMailer(false);
		if (\App\Core\AppConfig::debug('MAILER_DEBUG')) {
			$this->mailer->SMTPDebug = 2;
			$this->mailer->Debugoutput = function($str, $level) {
				if (strpos(strtolower($str), 'error') !== false || strpos(strtolower($str), 'failed') !== false) {
					\App\Log\Log::error(trim($str), 'Mailer');
				} else {
					\App\Log\Log::trace(trim($str), 'Mailer');
				}
			};
		}
		$this->mailer->XMailer = 'FreeCRM mailer';
		$heloHost = parse_url((string) \App\Core\AppConfig::main('site_URL'), PHP_URL_HOST);
		if (empty($heloHost) || !str_contains($heloHost, '.')) {
			$heloHost = gethostname() ?: 'localhost.localdomain';
		}
		$this->mailer->Hostname = $heloHost;
		$this->mailer->CharSet = \App\Core\AppConfig::main('default_charset');
	}

	/**
	 * Load configuration smtp by id
	 * @param int $smtpId Smtp ID
	 * @return $this mailer object itself
	 */
	public function loadSmtpByID($smtpId)
	{
		$this->smtp = \App\Email\Mail::getSmtpById($smtpId);
		$this->setSmtp();
		return $this;
	}

	/**
	 * Load configuration smtp
	 * @param array $smtpInfo
	 * @return $this mailer object itself
	 */
	public function loadSmtp($smtpInfo)
	{
		$this->smtp = $smtpInfo;
		$this->setSmtp();
		return $this;
	}

	/**
	 * Add mail to quote for send
	 * @param array $params
	 */
	public static function addMail($params)
	{
		$crmMessageId = (int) ($params['mail_message_id'] ?? 0);
		unset($params['mail_message_id']);

		$customParams = [];
		if (isset($params['params'])) {
			if (is_array($params['params'])) {
				$customParams = $params['params'];
			} elseif (is_string($params['params']) && $params['params'] !== '') {
				$decoded = \App\Utils\Json::decode($params['params']);
				if (is_array($decoded)) {
					$customParams = $decoded;
				}
			}
			unset($params['params']);
		}

		if (!empty($params['content'])) {
			$params['content'] = \App\Utils\TemplateStyles::inlineEmailCss($params['content']);
		}
		$params['status'] = \App\Core\AppConfig::module('Mail', 'MAILER_REQUIRED_ACCEPTATION_BEFORE_SENDING') ? 0 : 1;
		if (empty($params['smtp_id'])) {
			$params['smtp_id'] = \App\Email\Mail::getDefaultSmtp();
		}
		if (empty($params['owner'])) {
			$owner = \App\Modules\Users\Models\Record::getCurrentUserRealId();
			$params['owner'] = $owner ? $owner : 0;
		}
		$params['date'] = date('Y-m-d H:i:s');
		foreach (static::$quoteJsonColumn as $key) {
			if ($key === 'params' || !isset($params[$key])) {
				continue;
			}
			if (!is_array($params[$key])) {
				$params[$key] = [$params[$key]];
			}
			$params[$key] = \App\Utils\Json::encode($params[$key]);
		}
		if ($crmMessageId > 0) {
			$customParams['mail_message_id'] = $crmMessageId;
		}
		self::requireSenderRef($customParams);
		$params['params'] = \App\Utils\Json::encode($customParams);

		$db = \App\Db\Db::getInstance('admin');
		$db->createCommand()->insert('s_#__mail_queue', $params)->execute();
	}

	public static function smtpSenderRef(int $smtpId): string
	{
		if ($smtpId <= 0) {
			throw new \App\Exceptions\AppException('LBL_MAIL_SENDER_REF_REQUIRED');
		}

		return 'smtp:' . $smtpId;
	}

	/**
	 * @param array<string, mixed> $params
	 * @return array<string, mixed>
	 */
	public static function withSmtpSenderRef(array $params): array
	{
		if (($params['params']['sender_ref'] ?? '') !== '') {
			return $params;
		}
		$params['params'] = array_merge(
			is_array($params['params'] ?? null) ? $params['params'] : [],
			['sender_ref' => self::smtpSenderRef((int) ($params['smtp_id'] ?? 0))]
		);

		return $params;
	}

	/**
	 * @param array<string, mixed> $customParams
	 */
	public static function requireSenderRef(array $customParams): void
	{
		$senderRef = (string) ($customParams['sender_ref'] ?? '');
		if ($senderRef === '') {
			throw new \App\Exceptions\AppException('LBL_MAIL_SENDER_REF_REQUIRED');
		}
		if (!str_starts_with($senderRef, 'account:') && !str_starts_with($senderRef, 'smtp:')) {
			throw new \App\Exceptions\AppException('LBL_MAIL_SENDER_REF_INVALID');
		}
	}

	/**
	 * Get configuration smtp
	 * @param string|bool $key
	 * @return array
	 */
	public function getSmtp($key = false)
	{
		if ($key && isset($this->smtp[$key])) {
			return $this->smtp[$key];
		}
		return $this->smtp;
	}

	/**
	 * Set configuration smtp in mailer
	 */
	public function setSmtp()
	{
		if (!$this->smtp) {
			throw new \App\Exceptions\AppException('ERR_NO_SMTP_CONFIGURATION');
		}
		switch ($this->smtp['mailer_type']) {
			case 'smtp': $this->mailer->isSMTP();
				break;
			case 'sendmail': $this->mailer->isSendmail();
				break;
			case 'mail': $this->mailer->isMail();
				break;
			case 'qmail': $this->mailer->isQmail();
				break;
		}
		$this->mailer->Host = $this->smtp['host'];
		if (!empty($this->smtp['host']) && str_contains($this->smtp['host'], '.')) {
			$this->mailer->Hostname = $this->smtp['host'];
		}
		if (!empty($this->smtp['port'])) {
			$this->mailer->Port = $this->smtp['port'];
		}
		$this->mailer->SMTPSecure = $this->smtp['secure'];
		$this->mailer->SMTPAuth = isset($this->smtp['authentication']) ? (bool) $this->smtp['authentication'] : false;
		$this->mailer->Username = trim($this->smtp['username']);
		$password = trim($this->smtp['password']);
		$encryption = new \App\Security\Encryption();
		if ($encryption->isActive() && $password !== '') {
			$decrypted = $encryption->decrypt($password);
			if ($decrypted !== false && $decrypted !== '') {
				$password = $decrypted;
			}
		}
		$this->mailer->Password = $password;
		if ($this->smtp['options']) {
			$this->mailer->SMTPOptions = \App\Utils\Json::decode($this->smtp['options'], true);
		}
		if ($this->smtp['from_email']) {
			$this->mailer->From = $this->smtp['from_email'];
		}
		if ($this->smtp['from_name']) {
			$this->mailer->FromName = $this->smtp['from_name'];
		}
		if ($this->smtp['reply_to']) {
			$this->mailer->addReplyTo($this->smtp['reply_to']);
		}
	}

	/**
	 * Set subject
	 * @param string $subject
	 * @return $this mailer object itself
	 */
	public function subject($subject)
	{
		$this->mailer->Subject = $subject;
		return $this;
	}

	/**
	 * Creates a message from an HTML string, making modifications for inline images and backgrounds and creates a plain-text version by converting the HTML
	 * @param text $message
	 * @see PHPMailer::msgHTML()
	 * @return $this mailer object itself
	 */
	public function content($message)
	{
		$this->mailer->isHTML(true);
		$this->mailer->msgHTML(\App\Utils\TemplateStyles::inlineEmailCss($message));
		return $this;
	}

	/**
	 * Set the From and FromName properties.
	 * @param string $address
	 * @param string $name
	 * @return $this mailer object itself
	 */
	public function from($address, $name = '')
	{
		$this->mailer->From = $address;
		$this->mailer->FromName = $name;
		return $this;
	}

	/**
	 * Add a "To" address.
	 * @param string $address The email address to send to
	 * @param string $name
	 * @return $this mailer object itself
	 */
	public function to($address, $name = '')
	{
		$this->mailer->addAddress($address, $name);
		return $this;
	}

	/**
	 * Add a "CC" address.
	 * @note: This function works with the SMTP mailer on win32, not with the "mail" mailer.
	 * @param string $address The email address to send to
	 * @param string $name
	 * @return $this mailer object itself
	 */
	public function cc($address, $name = '')
	{
		$this->mailer->addCC($address, $name);
		return $this;
	}

	/**
	 * Add a "BCC" address.
	 * @note: This function works with the SMTP mailer on win32, not with the "mail" mailer.
	 * @param string $address The email address to send to
	 * @param string $name
	 * @return $this mailer object itself
	 */
	public function bcc($address, $name = '')
	{
		$this->mailer->addBCC($address, $name);
		return $this;
	}

	/**
	 * Add a "Reply-To" address.
	 * @param string $address The email address to reply to
	 * @param string $name
	 * @return $this mailer object itself
	 */
	public function replyTo($address, $name = '')
	{
		$this->mailer->addReplyTo($address, $name);
		return $this;
	}

	/**
	 * Add an attachment from a path on the filesystem.
	 * @param string $path Path to the attachment.
	 * @param string $name Overrides the attachment name.
	 * @return $this mailer object itself
	 */
	public function attachment($path, $name = '')
	{
		$this->mailer->addAttachment($path, $name);
		return $this;
	}

	/**
	 * Create a message and send it.
	 * @return boolean
	 */
	public function send()
	{
		if ($this->mailer->FromName === 'Root User') {
			$this->mailer->FromName = \App\Core\Company::getInstanceById()->get('name');
		}
		if ($this->mailer->send()) {
			\App\Log\Log::trace('Mailer sent mail', 'Mailer');
			return true;
		} else {
			\App\Log\Log::error('Mailer Error: ' . $this->mailer->ErrorInfo, 'Mailer');
		}
		return false;
	}

	/**
	 * Enable verbose SMTP logging for configuration tests.
	 */
	private function enableSmtpTestDebug(): void
	{
		$this->mailer->SMTPDebug = 2;
		$this->error = [];
		$this->mailer->Debugoutput = function ($str, $level) {
			if (strpos(strtolower($str), 'error') !== false || strpos(strtolower($str), 'failed') !== false) {
				$this->error[] = trim($str);
				\App\Log\Log::error(trim($str), 'Mailer');
			} else {
				\App\Log\Log::trace(trim($str), 'Mailer');
			}
		};
	}

	/**
	 * Apply short timeouts for SMTP configuration tests.
	 */
	private function applySmtpTestTimeout(): void
	{
		$this->mailer->Timeout = self::SMTP_TEST_TIMEOUT;
	}

	/**
	 * Test SMTP connection and authentication (no message is sent).
	 * @return array{result: bool, error?: string}
	 */
	public function testConnection(): array
	{
		if (($this->smtp['mailer_type'] ?? 'smtp') !== 'smtp') {
			return ['result' => true];
		}
		$this->applySmtpTestTimeout();
		$this->enableSmtpTestDebug();
		try {
			if (!$this->mailer->smtpConnect($this->mailer->SMTPOptions)) {
				return [
					'result' => false,
					'error' => $this->formatSmtpTestError('LBL_SMTP_CONNECT_FAILED', $this->mailer->ErrorInfo),
				];
			}
		} catch (\Exception $e) {
			return [
				'result' => false,
				'error' => $this->formatSmtpTestError('LBL_SMTP_CONNECT_FAILED', $e->getMessage()),
			];
		}
		return ['result' => true];
	}

	/**
	 * @param string $labelKey
	 * @param string $details
	 * @return string
	 */
	private function formatSmtpTestError(string $labelKey, string $details): string
	{
		$message = \App\Runtime\Vtiger_Language_Handler::translate($labelKey, 'Settings:MailSmtp');
		$details = trim($details);
		if ($details !== '') {
			$debug = trim(implode(PHP_EOL, $this->error));
			$message .= PHP_EOL . ($debug !== '' ? $debug : $details);
		}
		return $message;
	}

	/**
	 * Test mail server: connect first, then send a test message to the current user.
	 * @return array{result: bool, error?: string}
	 */
	public function test()
	{
		$this->applySmtpTestTimeout();
		$connectionTest = $this->testConnection();
		if (!$connectionTest['result']) {
			return $connectionTest;
		}
		$this->enableSmtpTestDebug();
		$currentUser = \App\User\CurrentUser::get();
		$this->to($currentUser->get('email1'));
		$template = \App\Email\Mail::getTempleteDetail('TestMailAboutTheMailServerConfiguration');
		if (!$template) {
			return ['result' => false, 'error' => \App\Runtime\Vtiger_Language_Handler::translate('LBL_NO_EMAIL_TEMPLATE')];
		}
		$textParser = \App\TextParser\TextParser::getInstanceById($currentUser->getId(), 'Users');
		$this->subject($textParser->setContent($template['subject'])->parse()->getContent());
		$this->content($textParser->setContent($template['content'])->parse()->getContent());
		$sent = $this->send();
		if (!$sent) {
			$error = trim(implode(PHP_EOL, $this->error));
			if ($error === '' && $this->mailer->ErrorInfo) {
				$error = $this->mailer->ErrorInfo;
			}
			return [
				'result' => false,
				'error' => $this->formatSmtpTestError('LBL_SMTP_SEND_TEST_FAILED', $error),
			];
		}
		return ['result' => true, 'error' => ''];
	}

	/**
	 * Empty allowlist = no filter (all recipients allowed).
	 */
	public static function isRecipientAllowedByAllowlist(string $email): bool
	{
		$allowlist = self::getRecipientAllowlist();
		if ($allowlist === []) {
			return true;
		}

		return self::recipientMatchesAllowlist($email, $allowlist);
	}

	/**
	 * True when allowlist is empty, or at least one active email field on the record matches.
	 */
	public static function recordHasAllowlistedEmail(\App\Modules\Base\Models\Record $record): bool
	{
		$allowlist = self::getRecipientAllowlist();
		if ($allowlist === []) {
			return true;
		}
		$moduleModel = $record->getModule();
		if (!$moduleModel) {
			return false;
		}
		foreach ($moduleModel->getFieldsByType('email') as $fieldName => $fieldModel) {
			if (!$fieldModel->isActiveField()) {
				continue;
			}
			$email = trim((string) $record->get($fieldName));
			if ($email !== '' && self::recipientMatchesAllowlist($email, $allowlist)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @return string[] Allowlist entries: exact addresses (contain @) or domains
	 */
	public static function getRecipientAllowlist(): array
	{
		return self::parseRecipientAllowlist(
			\App\Core\AppConfig::module('Mail', 'MAIL_FILTER_RECIPIENT_ALLOWLIST')
		);
	}

	/**
	 * @param list<string> $addresses
	 * @return list<string>
	 */
	public static function filterAddressListByAllowlist(array $addresses): array
	{
		$allowlist = self::getRecipientAllowlist();
		if ($allowlist === []) {
			return $addresses;
		}
		$filtered = [];
		foreach ($addresses as $email) {
			if (self::recipientMatchesAllowlist((string) $email, $allowlist)) {
				$filtered[] = (string) $email;
			}
		}

		return array_values(array_unique($filtered));
	}

	/**
	 * @param string|array<int|string, string>|null $config
	 * @return string[]
	 */
	public static function parseRecipientAllowlist(mixed $config): array
	{
		if ($config === null || $config === '' || $config === []) {
			return [];
		}
		$raw = is_array($config) ? $config : explode(',', (string) $config);
		$entries = [];
		foreach ($raw as $entry) {
			$entry = strtolower(trim((string) $entry));
			if ($entry !== '') {
				$entries[] = $entry;
			}
		}

		return array_values(array_unique($entries));
	}

	/**
	 * @param string[] $allowlist
	 * @return array|null Filtered queue row, or null when no allowed recipients remain
	 */
	public static function applyRecipientAllowlistFilter(array $rowQueue, array $allowlist): ?array
	{
		$to = self::filterRecipientMapByAllowlist($rowQueue['to'] ?? null, $allowlist);
		$cc = self::filterRecipientMapByAllowlist($rowQueue['cc'] ?? null, $allowlist);
		$bcc = self::filterRecipientMapByAllowlist($rowQueue['bcc'] ?? null, $allowlist);

		if ($to === [] && $cc === [] && $bcc === []) {
			return null;
		}

		if ($to === []) {
			if ($cc !== []) {
				$key = array_key_first($cc);
				$to = [$key => $cc[$key]];
				unset($cc[$key]);
			} elseif ($bcc !== []) {
				$key = array_key_first($bcc);
				$to = [$key => $bcc[$key]];
				unset($bcc[$key]);
			}
		}

		$rowQueue['to'] = \App\Utils\Json::encode($to);
		$rowQueue['cc'] = $cc !== [] ? \App\Utils\Json::encode($cc) : null;
		$rowQueue['bcc'] = $bcc !== [] ? \App\Utils\Json::encode($bcc) : null;

		return $rowQueue;
	}

	/**
	 * @param string[] $allowlist
	 */
	private static function filterRecipientMapByAllowlist(?string $json, array $allowlist): array
	{
		if ($json === null || $json === '') {
			return [];
		}
		$decoded = \App\Utils\Json::decode($json);
		if (!is_array($decoded)) {
			return [];
		}
		$filtered = [];
		foreach ($decoded as $email => $name) {
			if (is_numeric($email)) {
				$email = $name;
				$name = '';
			}
			if (self::recipientMatchesAllowlist((string) $email, $allowlist)) {
				$filtered[$email] = $name;
			}
		}

		return $filtered;
	}

	/**
	 * @param string[] $allowlist
	 */
	public static function recipientMatchesAllowlist(string $email, array $allowlist): bool
	{
		$email = strtolower(trim($email));
		foreach ($allowlist as $entry) {
			$entry = strtolower(trim((string) $entry));
			if ($entry === '') {
				continue;
			}
			if (str_contains($entry, '@')) {
				if ($email === $entry) {
					return true;
				}
				continue;
			}
			if (self::recipientMatchesDomain($email, $entry)) {
				return true;
			}
		}

		return false;
	}

	private static function recipientMatchesDomain(string $email, string $allowedDomain): bool
	{
		$email = strtolower(trim($email));
		$allowedDomain = strtolower(trim($allowedDomain));
		$at = strrpos($email, '@');
		if ($at === false) {
			return false;
		}
		$domain = substr($email, $at + 1);

		return $domain === $allowedDomain || str_ends_with($domain, '.' . $allowedDomain);
	}

	/**
	 * Build and send one queue row (instance must already be configured for smtp_id).
	 *
	 * @param array $rowQueue
	 * @return boolean
	 */
	public function deliverQueueRow(array $rowQueue): bool
	{
		$this->subject($rowQueue['subject'])->content($rowQueue['content']);
		if ($rowQueue['from']) {
			$from = \App\Utils\Json::decode($rowQueue['from']);
			$this->from($from['email'], $from['name']);
		}
		foreach (['cc', 'bcc'] as $key) {
			if ($rowQueue[$key]) {
				foreach (\App\Utils\Json::decode($rowQueue[$key]) as $email => $name) {
					if (is_numeric($email)) {
						$email = $name;
						$name = '';
					}
					$this->$key($email, $name);
				}
			}
		}
		$attachmentsToRemove = [];
		if ($rowQueue['attachments']) {
			$attachments = \App\Modules\Mail\Models\Attachment::resolveForSend(
				\App\Utils\Json::decode($rowQueue['attachments'])
			);
			foreach ($attachments as $path => $name) {
				$this->attachment($path, $name);
				$pathReal = realpath($path);
				if ($pathReal !== false && strpos($pathReal, 'cache' . DIRECTORY_SEPARATOR) !== false) {
					$attachmentsToRemove[] = $path;
				}
			}
		}
		if ($rowQueue['params']) {
			foreach (\App\Utils\Json::decode($rowQueue['params']) as $name => $param) {
				$this->sendCustomParams($name, $param, $this);
			}
		}
		$useKeepAlive = $this->mailer->SMTPKeepAlive;
		if ($this->getSmtp('individual_delivery')) {
			$status = true;
			foreach (\App\Utils\Json::decode($rowQueue['to']) as $email => $name) {
				if ($useKeepAlive) {
					$this->mailer->clearAllRecipients();
				}
				if (is_numeric($email)) {
					$email = $name;
					$name = '';
				}
				$this->to($email, $name);
				if (!$this->send()) {
					return false;
				}
			}
		} else {
			foreach (\App\Utils\Json::decode($rowQueue['to']) as $email => $name) {
				if (is_numeric($email)) {
					$email = $name;
					$name = '';
				}
				$this->to($email, $name);
			}
			$status = $this->send();
		}
		if ($status) {
			foreach ($attachmentsToRemove as $file) {
				unlink($file);
			}
		}
		return $status;
	}

	/**
	 * Adding additional parameters
	 * @param string $name
	 * @param mixed $param
	 * @param self $mailer
	 */
	public function sendCustomParams($name, $param, $mailer)
	{
		switch ($name) {
			case 'ics':
				$mailer->mailer->Ical = $param;
				break;
		}
	}
}
