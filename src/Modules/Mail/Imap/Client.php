<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * @project FreeCRM
 * @author bmankowski@gmail.com
 * @copyright (c) FreeCRM
 * @license FreeCRM Public License 1.0
 */

declare(strict_types=1);

namespace App\Modules\Mail\Imap;

use App\Modules\Mail\Models\Account;
use PHPMailer\PHPMailer\PHPMailer;
use Webklex\PHPIMAP\ClientManager;

class Client
{
	public static function createFromAccount(array $account, ?string $password = null): \Webklex\PHPIMAP\Client
	{
		$pass = $password ?? Account::getDecryptedPassword($account);
		$cm = new ClientManager();
		$protocol = self::mapSecure($account['imap_secure'] ?? 'ssl');
		return $cm->make([
			'host' => $account['imap_host'],
			'port' => (int) ($account['imap_port'] ?? 993),
			'encryption' => $protocol,
			'validate_cert' => (bool) ($account['imap_validate_cert'] ?? true),
			'username' => $account['username'],
			'password' => $pass,
			'protocol' => 'imap',
		]);
	}

	public static function testConnection(array $accountData, ?string $plainPassword = null): array
	{
		$accountData = self::resolvePersonalAccountData($accountData);
		$password = $plainPassword ?? '';
		if ($password === '' || $password === Account::passwordMask()) {
			if (!empty($accountData['id'])) {
				$stored = Account::getById((int) $accountData['id']);
				if ($stored === null) {
					return ['success' => false, 'error' => 'LBL_MAIL_ACCOUNT_NOT_FOUND'];
				}
				$row = (new \App\Db\Query())->from('u_yf_mail_accounts')->where(['id' => $accountData['id']])->one();
				$password = Account::getDecryptedPassword($row);
				$accountData = self::resolvePersonalAccountData(array_merge($row, $accountData));
			} else {
				return ['success' => false, 'error' => 'LBL_MAIL_PASSWORD_REQUIRED'];
			}
		}

		$client = null;
		try {
			$client = self::createFromAccount($accountData, $password);
			$client->connect();
		} catch (\Throwable $e) {
			return ['success' => false, 'error' => self::classifyImapError($e)];
		}

		$smtpResult = self::testSmtp($accountData, $password);
		if (!$smtpResult['success']) {
			self::disconnectClient($client);
			return $smtpResult;
		}

		try {
			$folderData = FolderTree::fetchFolderData($client);
			if (($folderData['folders'] ?? []) === []) {
				throw new \RuntimeException('No IMAP folders returned');
			}
		} catch (\Throwable) {
			self::disconnectClient($client);
			return ['success' => false, 'error' => 'LBL_MAIL_IMAP_FOLDER_LIST_FAILED'];
		}

		self::disconnectClient($client);

		return ['success' => true] + $folderData;
	}

	private static function disconnectClient(?\Webklex\PHPIMAP\Client $client): void
	{
		if ($client === null) {
			return;
		}
		try {
			$client->disconnect();
		} catch (\Throwable) {
		}
	}

	public static function testSmtp(array $accountData, string $password): array
	{
		$mailer = null;
		try {
			$mailer = new PHPMailer(true);
			$mailer->isSMTP();
			$mailer->Host = $accountData['smtp_host'];
			$mailer->Port = (int) ($accountData['smtp_port'] ?? 465);
			$secure = $accountData['smtp_secure'] ?? 'ssl';
			if ($secure === 'ssl') {
				$mailer->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
			} elseif ($secure === 'tls') {
				$mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
			}
			$mailer->SMTPAuth = true;
			$mailer->Username = $accountData['username'];
			$mailer->Password = $password;
			$mailer->Timeout = 10;
			$mailer->SMTPDebug = 0;
			if (!$mailer->smtpConnect()) {
				return ['success' => false, 'error' => self::classifySmtpError($mailer->ErrorInfo)];
			}
			$mailer->smtpClose();
			return ['success' => true];
		} catch (\Throwable $e) {
			$errorInfo = $mailer instanceof PHPMailer ? (string) $mailer->ErrorInfo : '';
			return ['success' => false, 'error' => self::classifySmtpError(trim($e->getMessage() . ' ' . $errorInfo))];
		}
	}

	private static function classifyImapError(\Throwable $e): string
	{
		$message = strtolower($e->getMessage());

		if (self::looksLikeAuthFailure($message)) {
			return 'LBL_MAIL_IMAP_AUTH_FAILED';
		}
		if (str_contains($message, 'certificate') || str_contains($message, 'ssl') || str_contains($message, 'tls')) {
			return 'LBL_MAIL_IMAP_TLS_FAILED';
		}
		if (str_contains($message, 'connection') || str_contains($message, 'timeout')
			|| str_contains($message, 'refused') || str_contains($message, 'unable to connect')
			|| str_contains($message, 'could not connect') || str_contains($message, 'getaddrinfo')) {
			return 'LBL_MAIL_IMAP_CONNECT_FAILED';
		}

		return 'LBL_MAIL_IMAP_CONNECT_FAILED';
	}

	private static function classifySmtpError(string $details): string
	{
		$message = strtolower(trim($details));

		if (self::looksLikeAuthFailure($message)) {
			return 'LBL_MAIL_SMTP_AUTH_FAILED';
		}
		if (str_contains($message, 'connection') || str_contains($message, 'timeout')
			|| str_contains($message, 'refused') || str_contains($message, 'unable to connect')
			|| str_contains($message, 'could not connect') || str_contains($message, 'getaddrinfo')) {
			return 'LBL_MAIL_SMTP_CONNECT_FAILED';
		}

		return 'LBL_MAIL_SMTP_CONNECT_FAILED';
	}

	private static function looksLikeAuthFailure(string $message): bool
	{
		return str_contains($message, 'authenticationfailed')
			|| str_contains($message, 'authentication failed')
			|| str_contains($message, 'invalid credentials')
			|| str_contains($message, 'could not authenticate')
			|| str_contains($message, 'auth failed')
			|| str_contains($message, 'login failed')
			|| str_contains($message, 'bad credentials')
			|| preg_match('/\b535\b/', $message) === 1
			|| preg_match('/\b534\b/', $message) === 1;
	}

	private static function resolvePersonalAccountData(array $accountData): array
	{
		if (($accountData['kind'] ?? 'personal') === 'group') {
			return $accountData;
		}

		$userId = (int) ($accountData['owner_user_id'] ?? 0);
		if ($userId <= 0 && !empty($accountData['id'])) {
			$userId = (int) ((new \App\Db\Query())
				->select('owner_user_id')
				->from('u_yf_mail_accounts')
				->where(['id' => (int) $accountData['id']])
				->scalar() ?: 0);
		}
		if ($userId <= 0) {
			return $accountData;
		}

		return Account::preparePersonalAccountData($userId, $accountData);
	}

	private static function mapSecure(string $secure): string
	{
		return match ($secure) {
			'tls' => 'tls',
			'none' => false,
			default => 'ssl',
		};
	}
}
