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

class Message
{
	public static function insertIfNew(int $accountId, int $uid, array $parsed): ?int
	{
		$db = \App\Db\Db::getInstance();
		$exists = (new \App\Db\Query())
			->from('u_yf_mail_messages')
			->where(['account_id' => $accountId, 'imap_uid' => $uid])
			->exists();
		if ($exists) {
			return null;
		}
		if (!empty($parsed['message_id'])) {
			$existsMsg = (new \App\Db\Query())
				->from('u_yf_mail_messages')
				->where(['message_id' => $parsed['message_id']])
				->exists();
			if ($existsMsg) {
				return null;
			}
		}

		$db->createCommand()->insert('u_yf_mail_messages', [
			'account_id' => $accountId,
			'smtp_id' => null,
			'sender_user_id' => null,
			'direction' => 'in',
			'imap_uid' => $uid,
			'message_id' => $parsed['message_id'] ?? null,
			'in_reply_to' => $parsed['in_reply_to'] ?? null,
			'references_hdr' => $parsed['references'] ?? null,
			'date_sent' => $parsed['date_sent'] ?? date('Y-m-d H:i:s'),
			'from_email' => $parsed['from_email'] ?? '',
			'from_name' => $parsed['from_name'] ?? null,
			'to_json' => json_encode($parsed['to'] ?? []),
			'cc_json' => !empty($parsed['cc']) ? json_encode($parsed['cc']) : null,
			'bcc_json' => null,
			'subject' => mb_substr((string) ($parsed['subject'] ?? ''), 0, 998),
			'body_html' => $parsed['body_html'] ?? null,
			'body_text' => $parsed['body_text'] ?? null,
			'has_attachments' => !empty($parsed['attachments']) ? 1 : 0,
			'size_bytes' => (int) ($parsed['size_bytes'] ?? 0),
		])->execute();
		$messageId = (int) $db->getLastInsertID();

		if (!empty($parsed['attachments'])) {
			Attachment::storeAll($messageId, $accountId, $parsed['attachments']);
		}

		$addresses = array_merge(
			[$parsed['from_email'] ?? ''],
			array_column($parsed['to'] ?? [], 'email'),
			array_column($parsed['cc'] ?? [], 'email')
		);
		\App\Modules\Mail\Models\Binding\Engine::bindMessage(['id' => $messageId, 'subject' => $parsed['subject'] ?? ''], $addresses);

		return $messageId;
	}

	public static function getById(int $id): ?array
	{
		return (new \App\Db\Query())->from('u_yf_mail_messages')->where(['id' => $id])->one() ?: null;
	}

	public static function insertOutbound(array $data): int
	{
		$db = \App\Db\Db::getInstance();
		$db->createCommand()->insert('u_yf_mail_messages', $data)->execute();
		return (int) $db->getLastInsertID();
	}
}
