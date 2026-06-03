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
	 * @param array<string, mixed> $params
	 */
	public static function sendViaAccount(int $userId, int $accountId, array $params): int
	{
		self::assertRateLimit($userId);
		Acl::assert($userId, Acl::ACTION_SEND, ['account_id' => $accountId]);

		$accountRow = (new \App\Db\Query())->from('u_yf_mail_accounts')->where(['id' => $accountId])->one();
		if (!$accountRow || (int) ($accountRow['active'] ?? 0) !== 1) {
			throw new \App\Exceptions\AppException('LBL_MAIL_ACCOUNT_INACTIVE');
		}

		$bodyHtml = \App\Utils\TemplateStyles::inlineEmailCss((string) ($params['body_html'] ?? ''));

		$envelope = [
			'to' => self::normalizeAddresses($params['to'] ?? []),
			'cc' => self::normalizeAddresses($params['cc'] ?? []),
			'bcc' => self::normalizeAddresses($params['bcc'] ?? []),
			'subject' => (string) ($params['subject'] ?? ''),
			'body_html' => $bodyHtml,
			'attachments' => $params['attachments'] ?? [],
		];

		$result = Sender::send($accountRow, $envelope);
		if (!$result['success']) {
			MailLog::write('send', (string) ($result['error'] ?? 'Send failed'), 'error', $accountId, $userId);
			throw new \App\Exceptions\AppException((string) ($result['error'] ?? 'Send failed'));
		}

		if (!empty($accountRow['append_sent']) && !empty($result['rfc822'])) {
			Appender::appendToSent($accountRow, $result['rfc822']);
		}

		$messageId = Message::insertOutbound([
			'account_id' => $accountId,
			'smtp_id' => null,
			'sender_user_id' => $userId,
			'direction' => 'out',
			'imap_uid' => null,
			'message_id' => $result['message_id'] ?? null,
			'date_sent' => date('Y-m-d H:i:s'),
			'from_email' => (string) $accountRow['username'],
			'from_name' => $accountRow['from_name'] ?? null,
			'to_json' => json_encode(self::formatAddressJson($envelope['to'])),
			'cc_json' => $envelope['cc'] !== [] ? json_encode(self::formatAddressJson($envelope['cc'])) : null,
			'bcc_json' => $envelope['bcc'] !== [] ? json_encode(self::formatAddressJson($envelope['bcc'])) : null,
			'subject' => mb_substr($envelope['subject'], 0, 998),
			'body_html' => $bodyHtml,
			'body_text' => strip_tags($bodyHtml),
			'has_attachments' => !empty($envelope['attachments']) ? 1 : 0,
			'size_bytes' => strlen($bodyHtml),
		]);

		self::bindOutbound($messageId, $params, array_merge($envelope['to'], $envelope['cc'], $envelope['bcc']));
		MailLog::write('send', 'Outbound via account ' . $accountId, 'info', $accountId, $userId, ['message_id' => $messageId]);
		return $messageId;
	}

	/**
	 * @param array<string, mixed> $params
	 */
	public static function recordSystemSend(int $userId, int $smtpId, array $params): int
	{
		$smtp = \App\Email\Mail::getSmtpById($smtpId);
		$fromEmail = is_array($smtp) ? (string) ($smtp['from_email'] ?? $smtp['username'] ?? '') : '';
		$fromName = is_array($smtp) ? (string) ($smtp['from_name'] ?? '') : '';
		$to = self::normalizeAddresses($params['to'] ?? []);
		$bodyHtml = \App\Utils\TemplateStyles::inlineEmailCss((string) ($params['body_html'] ?? $params['content'] ?? ''));

		$messageId = Message::insertOutbound([
			'account_id' => null,
			'smtp_id' => $smtpId,
			'sender_user_id' => $userId,
			'direction' => 'out',
			'imap_uid' => null,
			'message_id' => null,
			'date_sent' => date('Y-m-d H:i:s'),
			'from_email' => $fromEmail,
			'from_name' => $fromName ?: null,
			'to_json' => json_encode(self::formatAddressJson($to)),
			'cc_json' => null,
			'bcc_json' => null,
			'subject' => mb_substr((string) ($params['subject'] ?? ''), 0, 998),
			'body_html' => $bodyHtml,
			'body_text' => strip_tags($bodyHtml),
			'has_attachments' => 0,
			'size_bytes' => strlen($bodyHtml),
		]);

		self::bindOutbound($messageId, $params, $to);
		MailLog::write('send', 'Outbound via system SMTP ' . $smtpId, 'info', null, $userId, ['message_id' => $messageId]);
		return $messageId;
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
