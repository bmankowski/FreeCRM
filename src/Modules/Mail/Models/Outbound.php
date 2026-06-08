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
		$textParser->setParams(array_diff_key($params, array_flip(['subject', 'content', 'attachments', 'recordModel', 'subjectOverride'])));
		$sourceRecord = isset($params['sourceRecord']) ? (int) $params['sourceRecord'] : 0;
		$sourceModule = isset($params['sourceModule']) ? (string) $params['sourceModule'] : '';
		if ($sourceRecord > 0 && $sourceModule !== '') {
			$textParser->setSourceRecord($sourceRecord, $sourceModule);
		}

		$mailParams = self::buildMailParams($params, $template);
		$crmMessageId = self::prepare($userId, $senderRef, $mailParams);

		$textParser->setMailMessageId($crmMessageId);
		$subject = $textParser->setContent($template['subject'] ?? '')->parse()->getContent();
		$content = $textParser->setContent($template['content'] ?? '')->parse()->getContent();
		if (!empty($params['subjectOverride'])) {
			$subject = (string) $params['subjectOverride'];
		}

		self::finalizeParsedContent($crmMessageId, $subject, $content);
		$mailParams['subject'] = $subject;
		$mailParams['body_html'] = $content;
		$mailParams['content'] = $content;
		if (isset($template['attachments'])) {
			$mailParams['attachments'] = array_merge(
				empty($mailParams['attachments']) ? [] : $mailParams['attachments'],
				$template['attachments']
			);
		}

		self::deliver($userId, $senderRef, $crmMessageId, $mailParams);

		return $crmMessageId;
	}

	/**
	 * Send pre-built HTML (compose) through prepare → finalize → deliver.
	 *
	 * @param array<string, mixed> $params
	 */
	public static function sendRaw(int $userId, string $senderRef, array $params): int
	{
		$crmMessageId = self::prepare($userId, $senderRef, $params);
		$body = (string) ($params['body_html'] ?? $params['content'] ?? '');
		self::finalizeParsedContent($crmMessageId, (string) ($params['subject'] ?? ''), $body);
		$params['body_html'] = $body;
		$params['content'] = $body;
		self::deliver($userId, $senderRef, $crmMessageId, $params);

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
	 * @param array<string, mixed> $params
	 */
	public static function deliver(int $userId, string $senderRef, int $crmMessageId, array $params): void
	{
		if (str_starts_with($senderRef, 'account:')) {
			self::deliverViaAccount($userId, (int) substr($senderRef, 8), $crmMessageId, $params);

			return;
		}
		if (str_starts_with($senderRef, 'smtp:')) {
			self::deliverViaSmtpQueue($userId, (int) substr($senderRef, 5), $crmMessageId, $params);

			return;
		}
		self::markFailed($crmMessageId);
		throw new \App\Exceptions\AppException('Invalid sender');
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
	private static function deliverViaAccount(int $userId, int $accountId, int $crmMessageId, array $params): void
	{
		self::assertRateLimit($userId);
		Acl::assert($userId, Acl::ACTION_SEND, ['account_id' => $accountId]);

		$accountRow = (new \App\Db\Query())->from('u_yf_mail_accounts')->where(['id' => $accountId])->one();
		if (!$accountRow || (int) ($accountRow['active'] ?? 0) !== 1) {
			self::markFailed($crmMessageId);
			throw new \App\Exceptions\AppException('LBL_MAIL_ACCOUNT_INACTIVE');
		}

		$row = Message::getById($crmMessageId);
		$bodyHtml = (string) ($row['body_html'] ?? $params['body_html'] ?? '');
		$envelope = [
			'to' => self::normalizeAddresses($params['to'] ?? []),
			'cc' => self::normalizeAddresses($params['cc'] ?? []),
			'bcc' => self::normalizeAddresses($params['bcc'] ?? []),
			'subject' => (string) ($row['subject'] ?? $params['subject'] ?? ''),
			'body_html' => $bodyHtml,
			'attachments' => $params['attachments'] ?? [],
		];

		$result = Sender::send($accountRow, $envelope);
		if (!$result['success']) {
			self::markFailed($crmMessageId);
			MailLog::write('send', (string) ($result['error'] ?? 'Send failed'), 'error', $accountId, $userId);
			throw new \App\Exceptions\AppException((string) ($result['error'] ?? 'Send failed'));
		}

		if (!empty($accountRow['append_sent']) && !empty($result['rfc822'])) {
			Appender::appendToSent($accountRow, $result['rfc822']);
		}

		self::markSent($crmMessageId, isset($result['message_id']) ? (string) $result['message_id'] : null);
		MailLog::write('send', 'Outbound via account ' . $accountId, 'info', $accountId, $userId, ['message_id' => $crmMessageId]);
	}

	/**
	 * @param array<string, mixed> $params
	 */
	private static function deliverViaSmtpQueue(int $userId, int $smtpId, int $crmMessageId, array $params): void
	{
		$row = Message::getById($crmMessageId);
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
			['mail_message_id' => $crmMessageId]
		));
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
		throw new \App\Exceptions\AppException('Invalid sender');
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
