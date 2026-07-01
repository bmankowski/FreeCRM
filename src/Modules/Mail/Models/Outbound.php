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

namespace App\Modules\Mail\Models;

use App\Modules\Mail\Imap\Appender;
use App\Modules\Mail\Models\Binding\Engine;
use App\Modules\Mail\Smtp\Sender;

class Outbound
{
	public static function assertRateLimit(int $userId): void
	{
		$limit = (int) (\App\Core\AppConfig::module('Mail', 'send_rate_limit_per_minute') ?? 60);
		$count = (new \App\Db\Query())
			->from('u_yf_mail_log')
			->where(['user_id' => $userId, 'action' => 'send'])
			->andWhere(['>=', 'created_at', date('Y-m-d H:i:s', time() - 60)])
			->count();
		if ($count >= $limit) {
			throw new \App\Exceptions\AppException('LBL_MAIL_RATE_LIMIT');
		}
	}

	/**
	 * Parse template with a prepared CRM message id embedded in LinkAction tokens, then send.
	 *
	 * @param array<string, mixed> $params
	 */
	public static function sendFromTemplate(int $userId, string $senderRef, array $params, array $template): int
	{
		$recordModel = self::resolveRecordModel($params);
		$textParser = $recordModel
			? \App\TextParser\TextParser::getInstanceByModel($recordModel)
			: \App\TextParser\TextParser::getInstance((string) ($params['moduleName'] ?? ''));
		if (!empty($params['language'])) {
			$textParser->setLanguage($params['language']);
		}
		$textParser->setParams(array_diff_key($params, array_flip([
			'subject', 'content', 'attachments', 'recordModel', 'subjectOverride', 'contentOverride', 'composeAttachmentTokens',
		])));
		$sourceRecord = isset($params['sourceRecord']) ? (int) $params['sourceRecord'] : 0;
		$sourceModule = isset($params['sourceModule']) ? (string) $params['sourceModule'] : '';
		if ($sourceRecord > 0 && $sourceModule !== '') {
			$textParser->setSourceRecord($sourceRecord, $sourceModule);
		}

		$attachmentInput = [];
		if (!empty($params['attachments']) && is_array($params['attachments'])) {
			$attachmentInput = array_merge($attachmentInput, $params['attachments']);
		}
		if (isset($template['attachments']) && is_array($template['attachments'])) {
			$attachmentInput = array_merge($attachmentInput, $template['attachments']);
		}
		$templateId = (int) ($params['template'] ?? $template['emailtemplatesid'] ?? 0);
		if ($templateId > 0) {
			\App\Modules\EmailTemplates\Models\TemplateAttachment::assertAllFilesPresent($templateId);
		}
		$resolvedAttachments = Attachment::resolveForSend($attachmentInput);
		if ($templateId > 0) {
			$expectedCount = count(\App\Modules\EmailTemplates\Models\TemplateAttachment::getDocumentIdsForTemplate($templateId));
			if ($expectedCount > 0 && count($resolvedAttachments) < $expectedCount) {
				\App\Log\Log::warning(
					'Template attachments unresolved at send: templateId=' . $templateId
					. ' expected=' . $expectedCount
					. ' resolved=' . count($resolvedAttachments),
					'Mail'
				);
				throw new \App\Exceptions\AppException('LBL_ATTACHMENT_FILE_MISSING');
			}
		}

		$mailParams = self::buildMailParams($params, $template);
		$mailParams['attachments'] = $resolvedAttachments;
		$crmMessageId = self::prepare($userId, $senderRef, $mailParams);

		$textParser->setMailMessageId($crmMessageId);
		$subject = $textParser->setContent($template['subject'] ?? '')->parse()->getContent();
		$content = $textParser->setContent($template['content'] ?? '')->parse()->getContent();
		$parsedTemplateContent = $content;
		if (!empty($params['subjectOverride'])) {
			$subject = $textParser->setContent((string) $params['subjectOverride'])->parse()->getContent();
		}
		if (!empty($params['contentOverride'])) {
			$override = (string) $params['contentOverride'];
			if (str_contains($override, 'src=""')
				&& preg_match('/<img src="[^"]+" width="[12]" height="[12]"[^>]*>/', $parsedTemplateContent, $trackingImg)) {
				$override = (string) preg_replace(
					'/<img src="" width="[12]" height="[12]"[^>]*>/',
					$trackingImg[0],
					$override,
					1
				);
			}
			$content = $textParser->setContent($override)->parse()->getContent();
		}

		self::finalizeParsedContent($crmMessageId, $subject, $content);
		$mailParams['subject'] = $subject;
		$mailParams['body_html'] = $content;
		$mailParams['content'] = $content;
		$mailParams['attachments'] = $resolvedAttachments;

		[$accountId] = self::resolveSender($senderRef);
		Attachment::storeFromPaths($crmMessageId, $accountId ?? 0, $resolvedAttachments);
		$storedAttachments = Attachment::pathMapForSend($crmMessageId);
		if ($storedAttachments !== []) {
			$mailParams['attachments'] = $storedAttachments;
		}

		$mailParams = self::applyRecipientDomainPolicy($mailParams);
		self::enqueue($userId, $senderRef, $crmMessageId, $mailParams);

		$composeTokens = $params['composeAttachmentTokens'] ?? [];
		if (is_array($composeTokens) && $composeTokens !== []) {
			ComposeAttachment::deleteTokens($userId, $composeTokens);
		}

		return $crmMessageId;
	}

	/**
	 * Send pre-built HTML (compose) through prepare → finalize → deliver.
	 *
	 * @param array<string, mixed> $params
	 */
	public static function sendRaw(int $userId, string $senderRef, array $params): int
	{
		if (!empty($params['attachments']) && is_array($params['attachments'])) {
			$params['attachments'] = Attachment::resolveForSend($params['attachments']);
		}
		$crmMessageId = self::prepare($userId, $senderRef, $params);
		$body = (string) ($params['body_html'] ?? $params['content'] ?? '');
		self::finalizeParsedContent($crmMessageId, (string) ($params['subject'] ?? ''), $body);
		$params['body_html'] = $body;
		$params['content'] = $body;
		[$accountId] = self::resolveSender($senderRef);
		if (!empty($params['attachments'])) {
			Attachment::storeFromPaths($crmMessageId, $accountId ?? 0, $params['attachments']);
			$storedAttachments = Attachment::pathMapForSend($crmMessageId);
			if ($storedAttachments !== []) {
				$params['attachments'] = $storedAttachments;
			}
		}
		$params = self::applyRecipientDomainPolicy($params);
		self::enqueue($userId, $senderRef, $crmMessageId, $params);
		$composeTokens = $params['composeAttachmentTokens'] ?? [];
		if (is_array($composeTokens) && $composeTokens !== []) {
			ComposeAttachment::deleteTokens($userId, $composeTokens);
		}

		return $crmMessageId;
	}

	/**
	 * @param array<string, mixed> $params
	 */
	public static function prepare(int $userId, string $senderRef, array $params): int
	{
		[$accountId, $smtpId, $fromEmail, $fromName] = self::resolveSender($senderRef);
		$to = self::normalizeAddresses($params['to'] ?? []);

		$messageId = Message::insertOutbound([
			'account_id' => $accountId,
			'smtp_id' => $smtpId,
			'sender_user_id' => $userId,
			'direction' => 'out',
			'send_status' => 'prepared',
			'imap_uid' => null,
			'message_id' => null,
			'date_sent' => date('Y-m-d H:i:s'),
			'from_email' => $fromEmail,
			'from_name' => $fromName,
			'to_json' => json_encode(self::formatAddressJson($to)),
			'cc_json' => !empty($params['cc']) ? json_encode(self::formatAddressJson(self::normalizeAddresses($params['cc']))) : null,
			'bcc_json' => !empty($params['bcc']) ? json_encode(self::formatAddressJson(self::normalizeAddresses($params['bcc']))) : null,
			'subject' => mb_substr((string) ($params['subject'] ?? ''), 0, 998),
			'body_html' => null,
			'body_text' => null,
			'has_attachments' => !empty($params['attachments']) ? 1 : 0,
			'size_bytes' => 0,
		]);

		self::bindOutbound($messageId, $params, $to);

		return $messageId;
	}

	/**
	 * Template send from a params bag (workflow, modal mass-send, cron helpers).
	 *
	 * @param array<string, mixed> $params
	 */
	public static function sendFromTemplateParams(array $params): bool
	{
		if (empty($params['template'])) {
			return false;
		}
		$template = \App\Email\Mail::getTemplete($params['template']);
		if (!$template) {
			return false;
		}

		$userId = self::resolveSendUserId($params);
		$senderRef = (string) ($params['senderRef'] ?? '');
		if ($senderRef === '') {
			throw new \App\Exceptions\AppException('LBL_MAIL_SENDER_REF_REQUIRED');
		}

		try {
			self::sendFromTemplate($userId, $senderRef, $params, $template);
		} catch (\App\Exceptions\AppException $e) {
			throw $e;
		} catch (\Throwable $e) {
			\App\Log\Log::error('sendFromTemplate failed: ' . $e->getMessage(), 'Mail');

			return false;
		}

		return true;
	}

	/**
	 * @param array<string, mixed> $params
	 */
	private static function resolveSendUserId(array $params): int
	{
		if (!empty($params['owner'])) {
			return (int) $params['owner'];
		}
		if (!empty($params['userId'])) {
			return (int) $params['userId'];
		}
		$owner = \App\Modules\Users\Models\Record::getCurrentUserRealId();

		return $owner ? (int) $owner : 1;
	}

	public static function finalizeParsedContent(int $crmMessageId, string $subject, string $bodyHtml): void
	{
		$bodyHtml = \App\Utils\TemplateStyles::inlineEmailCss($bodyHtml);
		Message::updateOutbound($crmMessageId, [
			'subject' => mb_substr($subject, 0, 998),
			'body_html' => $bodyHtml,
			'body_text' => strip_tags($bodyHtml),
			'size_bytes' => strlen($bodyHtml),
		]);
	}

	/**
	 * Cron entry point for every row in s_yf_mail_queue.
	 */
	public static function deliverFromQueueRow(array $rowQueue, int $userId): bool
	{
		if (\App\Core\AppConfig::main('systemMode') === 'demo') {
			return true;
		}

		$crmMessageId = self::crmMessageIdFromQueueRow($rowQueue);
		$senderRef = self::resolveSenderRefFromQueueRow($rowQueue);
		if ($senderRef === '') {
			self::markFailedIfTracked($crmMessageId);
			throw new \App\Exceptions\AppException('LBL_MAIL_SENDER_REF_REQUIRED');
		}

		$allowedDomains = \App\Email\Mailer::getSendOnlyToDomains();
		if ($allowedDomains !== []) {
			$filtered = \App\Email\Mailer::applySendOnlyToDomainFilter($rowQueue, $allowedDomains);
			if ($filtered === null) {
				self::markFailedIfTracked($crmMessageId);

				return false;
			}
			$rowQueue = $filtered;
		}

		$to = self::decodeQueueRecipients($rowQueue['to'] ?? null);
		if ($to === []) {
			self::markFailedIfTracked($crmMessageId);

			return false;
		}

		$attachments = [];
		if (!empty($rowQueue['attachments'])) {
			$attachments = Attachment::resolveForSend(
				\App\Utils\Json::decode((string) $rowQueue['attachments']) ?: []
			);
		}
		if ($attachments === [] && $crmMessageId > 0) {
			$attachments = Attachment::pathMapForSend($crmMessageId);
		}

		$params = [
			'to' => $to,
			'cc' => self::decodeQueueRecipients($rowQueue['cc'] ?? null),
			'bcc' => self::decodeQueueRecipients($rowQueue['bcc'] ?? null),
			'subject' => (string) ($rowQueue['subject'] ?? ''),
			'body_html' => (string) ($rowQueue['content'] ?? ''),
			'attachments' => $attachments,
		];

		try {
			if (str_starts_with($senderRef, 'account:')) {
				self::sendViaAccount($userId, (int) substr($senderRef, 8), $crmMessageId, $params);
			} elseif (str_starts_with($senderRef, 'smtp:')) {
				$smtpId = (int) substr($senderRef, 5);
				$status = (new \App\Email\Mailer())->loadSmtpByID($smtpId)->deliverQueueRow($rowQueue);
				if (!$status) {
					self::markFailedIfTracked($crmMessageId);
					throw new \App\Exceptions\AppException('Send failed');
				}
				self::markSentIfTracked($crmMessageId);
				if ($crmMessageId > 0) {
					MailLog::write('send', 'Outbound via smtp ' . $smtpId, 'info', null, $userId, ['message_id' => $crmMessageId]);
				}
			} else {
				self::markFailedIfTracked($crmMessageId);
				throw new \App\Exceptions\AppException('LBL_MAIL_SENDER_REF_INVALID');
			}
		} catch (\App\Exceptions\AppException $e) {
			self::markFailedIfTracked($crmMessageId);
			throw $e;
		} catch (\Throwable $e) {
			self::markFailedIfTracked($crmMessageId);
			\App\Log\Log::error(
				'deliverFromQueueRow failed queue id=' . (int) ($rowQueue['id'] ?? 0) . ': ' . $e->getMessage(),
				'Mail'
			);
			throw $e;
		}

		return true;
	}

	private static function resolveSenderRefFromQueueRow(array $rowQueue): string
	{
		$paramsJson = \App\Utils\Json::decode((string) ($rowQueue['params'] ?? ''));
		if (\is_array($paramsJson)) {
			return (string) ($paramsJson['sender_ref'] ?? '');
		}

		return '';
	}

	private static function markFailedIfTracked(int $crmMessageId): void
	{
		if ($crmMessageId > 0) {
			self::markFailed($crmMessageId);
		}
	}

	private static function markSentIfTracked(int $crmMessageId, ?string $rfcMessageId = null): void
	{
		if ($crmMessageId > 0) {
			self::markSent($crmMessageId, $rfcMessageId);
		}
	}

	/**
	 * @param array<string, mixed> $params
	 */
	private static function applyRecipientDomainPolicy(array $params): array
	{
		if (\App\Email\Mailer::getSendOnlyToDomains() === []) {
			return $params;
		}
		foreach (['to', 'cc', 'bcc'] as $key) {
			if (!isset($params[$key])) {
				continue;
			}
			$params[$key] = \App\Email\Mailer::filterAddressListByAllowedDomains(
				self::normalizeAddresses($params[$key])
			);
		}
		if (self::normalizeAddresses($params['to'] ?? []) === []) {
			throw new \App\Exceptions\AppException('LBL_MAIL_RECIPIENT_DOMAIN_NOT_ALLOWED');
		}

		return $params;
	}

	/**
	 * @param array<string, mixed> $params
	 */
	private static function enqueue(int $userId, string $senderRef, int $crmMessageId, array $params): void
	{
		$row = Message::getById($crmMessageId);
		$smtpId = \App\Email\Mail::getDefaultSmtp();
		if (str_starts_with($senderRef, 'smtp:')) {
			$smtpId = (int) substr($senderRef, 5);
		}
		$queueParams = [
			'smtp_id' => $smtpId,
			'subject' => (string) ($row['subject'] ?? $params['subject'] ?? ''),
			'content' => (string) ($row['body_html'] ?? $params['content'] ?? $params['body_html'] ?? ''),
			'to' => $params['to'] ?? [],
			'source_module' => $params['sourceModule'] ?? $params['source_module'] ?? null,
			'source_id' => $params['sourceRecord'] ?? $params['source_id'] ?? null,
			'owner' => $userId,
		];
		if (!empty($params['moduleName']) && !empty($params['recordId'])) {
			$queueParams['source_module'] = (string) $params['moduleName'];
			$queueParams['source_id'] = (int) $params['recordId'];
		}
		if (!empty($params['email_template_id'])) {
			$queueParams['email_template_id'] = (int) $params['email_template_id'];
		} elseif (!empty($params['template'])) {
			$queueParams['email_template_id'] = (int) $params['template'];
		}
		if (!empty($params['attachments'])) {
			$queueParams['attachments'] = $params['attachments'];
		}
		if (!empty($params['cc'])) {
			$queueParams['cc'] = $params['cc'];
		}
		if (!empty($params['bcc'])) {
			$queueParams['bcc'] = $params['bcc'];
		}

		\App\Email\Mailer::addMail(array_merge(
			array_intersect_key($queueParams, array_flip(\App\Email\Mailer::$quoteColumn)),
			[
				'mail_message_id' => $crmMessageId,
				'params' => ['sender_ref' => $senderRef],
			]
		));
	}

	/**
	 * @return list<string>
	 */
	private static function decodeQueueRecipients(?string $json): array
	{
		if ($json === null || $json === '') {
			return [];
		}
		$decoded = \App\Utils\Json::decode($json);
		if (!\is_array($decoded)) {
			return [];
		}
		$addresses = [];
		foreach ($decoded as $email => $name) {
			if (\is_numeric($email)) {
				$email = $name;
			}
			$email = trim((string) $email);
			if ($email !== '') {
				$addresses[] = $email;
			}
		}

		return array_values(array_unique($addresses));
	}

	public static function markSent(int $crmMessageId, ?string $rfcMessageId = null): void
	{
		$update = [
			'send_status' => 'sent',
			'date_sent' => date('Y-m-d H:i:s'),
		];
		if ($rfcMessageId !== null && $rfcMessageId !== '') {
			$update['message_id'] = $rfcMessageId;
		}
		Message::updateOutbound($crmMessageId, $update);
	}

	public static function markFailed(int $crmMessageId): void
	{
		Message::updateOutbound($crmMessageId, ['send_status' => 'failed']);
	}

	public static function crmMessageIdFromQueueRow(array $rowQueue): int
	{
		if (empty($rowQueue['params'])) {
			return 0;
		}
		$params = \App\Utils\Json::decode($rowQueue['params']);
		if (!is_array($params)) {
			return 0;
		}

		return (int) ($params['mail_message_id'] ?? 0);
	}

	/**
	 * @param array<string, mixed> $params
	 */
	private static function sendViaAccount(int $userId, int $accountId, int $crmMessageId, array $params): void
	{
		self::assertRateLimit($userId);
		Acl::assert($userId, Acl::ACTION_SEND, ['account_id' => $accountId]);

		$accountRow = (new \App\Db\Query())->from('u_yf_mail_accounts')->where(['id' => $accountId])->one();
		if (!$accountRow || (int) ($accountRow['active'] ?? 0) !== 1) {
			self::markFailedIfTracked($crmMessageId);
			throw new \App\Exceptions\AppException('LBL_MAIL_ACCOUNT_INACTIVE');
		}

		$row = $crmMessageId > 0 ? Message::getById($crmMessageId) : null;
		$bodyHtml = (string) ($row['body_html'] ?? $params['body_html'] ?? '');
		$attachments = $params['attachments'] ?? [];
		if (is_array($attachments)) {
			$attachments = Attachment::resolveForSend($attachments);
		}
		$subject = (string) ($params['subject'] ?? '');
		if ($row !== null) {
			$subject = (string) ($row['subject'] ?? $subject);
		}
		$envelope = [
			'to' => self::normalizeAddresses($params['to'] ?? []),
			'cc' => self::normalizeAddresses($params['cc'] ?? []),
			'bcc' => self::normalizeAddresses($params['bcc'] ?? []),
			'subject' => $subject,
			'body_html' => $bodyHtml,
			'attachments' => $attachments,
		];

		$result = Sender::send($accountRow, $envelope);
		if (!$result['success']) {
			self::markFailedIfTracked($crmMessageId);
			MailLog::write('send', (string) ($result['error'] ?? 'Send failed'), 'error', $accountId, $userId);
			throw new \App\Exceptions\AppException((string) ($result['error'] ?? 'Send failed'));
		}

		if (!empty($accountRow['append_sent']) && !empty($result['rfc822'])) {
			Appender::appendToSent($accountRow, $result['rfc822']);
		}

		self::markSentIfTracked(
			$crmMessageId,
			isset($result['message_id']) ? (string) $result['message_id'] : null
		);
		if ($crmMessageId > 0) {
			MailLog::write('send', 'Outbound via account ' . $accountId, 'info', $accountId, $userId, ['message_id' => $crmMessageId]);
		}
	}

	/**
	 * @return array{0:?int,1:?int,2:string,3:?string}
	 */
	private static function resolveSender(string $senderRef): array
	{
		if (str_starts_with($senderRef, 'account:')) {
			$accountId = (int) substr($senderRef, 8);
			$accountRow = (new \App\Db\Query())->from('u_yf_mail_accounts')->where(['id' => $accountId])->one();
			if (!$accountRow) {
				throw new \App\Exceptions\AppException('LBL_MAIL_ACCOUNT_INACTIVE');
			}

			return [
				$accountId,
				null,
				(string) $accountRow['username'],
				$accountRow['from_name'] ?? null,
			];
		}
		if (str_starts_with($senderRef, 'smtp:')) {
			$smtpId = (int) substr($senderRef, 5);
			$smtp = \App\Email\Mail::getSmtpById($smtpId);
			$fromEmail = is_array($smtp) ? (string) ($smtp['from_email'] ?? $smtp['username'] ?? '') : '';
			$fromName = is_array($smtp) ? (string) ($smtp['from_name'] ?? '') : '';

			return [null, $smtpId, $fromEmail, $fromName !== '' ? $fromName : null];
		}
		throw new \App\Exceptions\AppException('LBL_MAIL_SENDER_REF_INVALID');
	}

	/**
	 * @param array<string, mixed> $params
	 * @return array<string, mixed>
	 */
	private static function buildMailParams(array $params, array $template): array
	{
		$mailParams = $params;
		if (empty($mailParams['smtp_id'])) {
			$mailParams['smtp_id'] = \App\Email\Mail::resolveTemplateSmtpId($template);
		}
		if (!empty($params['template'])) {
			$mailParams['email_template_id'] = (int) $params['template'];
		} elseif (!empty($template['emailtemplatesid'])) {
			$mailParams['email_template_id'] = (int) $template['emailtemplatesid'];
		}
		if (!empty($params['recordModel']) && is_object($params['recordModel'])) {
			$recordModel = $params['recordModel'];
			$mailParams['sourceModule'] = $recordModel->getModuleName();
			$mailParams['sourceRecord'] = (int) $recordModel->getId();
			$mailParams['moduleName'] = $recordModel->getModuleName();
			$mailParams['recordId'] = (int) $recordModel->getId();
		} elseif (!empty($params['moduleName']) && !empty($params['recordId'])) {
			$mailParams['sourceModule'] = (string) $params['moduleName'];
			$mailParams['sourceRecord'] = (int) $params['recordId'];
		} elseif (!empty($params['sourceModule']) && !empty($params['sourceRecord'])) {
			$mailParams['sourceModule'] = (string) $params['sourceModule'];
			$mailParams['sourceRecord'] = (int) $params['sourceRecord'];
		}

		return $mailParams;
	}

	/**
	 * @param array<string, mixed> $params
	 */
	private static function resolveRecordModel(array $params): ?\App\Modules\Base\Models\Record
	{
		if (!empty($params['recordModel']) && is_object($params['recordModel'])) {
			return $params['recordModel'];
		}
		$moduleName = isset($params['moduleName']) ? (string) $params['moduleName'] : '';
		if ($moduleName !== '' && !empty($params['recordId'])) {
			return \App\Modules\Base\Models\Record::getInstanceById((int) $params['recordId'], $moduleName);
		}

		return null;
	}

	/**
	 * @param array<string, mixed> $params
	 * @param list<string> $addresses
	 */
	private static function bindOutbound(int $messageId, array $params, array $addresses): void
	{
		$sourceModule = (string) ($params['sourceModule'] ?? $params['source_module'] ?? '');
		$sourceRecord = (int) ($params['sourceRecord'] ?? $params['source_id'] ?? 0);
		$hasComposeContext = $sourceModule !== '' && $sourceRecord > 0;

		if ($hasComposeContext) {
			Engine::link($messageId, $sourceModule, $sourceRecord, 'auto', 'compose_source');
		}

		$recipientModule = (string) ($params['moduleName'] ?? $params['recordModule'] ?? '');
		$recipientId = (int) ($params['recordId'] ?? 0);
		$knownRecipient = $recipientId > 0 && $recipientModule !== '';
		if ($knownRecipient) {
			$sameAsSource = $hasComposeContext
				&& $recipientModule === $sourceModule
				&& $recipientId === $sourceRecord;
			if (!$sameAsSource) {
				$matchField = (string) ($params['field'] ?? '');
				Engine::link(
					$messageId,
					$recipientModule,
					$recipientId,
					'auto',
					$matchField !== '' ? $matchField : 'compose_recipient'
				);
			}
		}

		Engine::bindMessage(
			['id' => $messageId, 'subject' => $params['subject'] ?? ''],
			$addresses,
			$hasComposeContext || $knownRecipient
		);
	}

	/**
	 * @param string|list<string|array{email: string}> $addresses
	 * @return list<string>
	 */
	private static function normalizeAddresses(string|array $addresses): array
	{
		if (is_string($addresses)) {
			$addresses = $addresses === ''
				? []
				: array_filter(array_map('trim', explode(',', $addresses)));
		}
		$out = [];
		foreach ($addresses as $addr) {
			if (is_array($addr)) {
				$email = trim((string) ($addr['email'] ?? ''));
			} else {
				$email = trim((string) $addr);
			}
			if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
				$out[] = $email;
			}
		}
		return array_values(array_unique($out));
	}

	/**
	 * @param list<string> $emails
	 * @return list<array{email: string}>
	 */
	private static function formatAddressJson(array $emails): array
	{
		return array_map(static fn (string $email): array => ['email' => $email], $emails);
	}
}
