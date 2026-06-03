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
use App\Modules\Mail\Models\Message;

class Fetcher
{
	public static function fetchAccount(array $account): int
	{
		$rawAccount = (new \App\Db\Query())->from('u_yf_mail_accounts')->where(['id' => $account['id']])->one();
		$userId = (int) ($rawAccount['owner_user_id'] ?? 0);
		if ($userId > 0 && ($rawAccount['kind'] ?? '') === 'personal') {
			$rawAccount = Account::applyPersonalDefaults($rawAccount, $userId);
		}
		$client = Client::createFromAccount($rawAccount);
		$client->connect();
		$folderName = $account['imap_folder_inbox'] ?? 'INBOX';
		$folder = $client->getFolder($folderName);
		$lastUid = (int) ($account['last_uid'] ?? 0);
		$messages = $folder->messages()->all()->get();
		$maxUid = $lastUid;
		$count = 0;

		foreach ($messages as $message) {
			$uid = (int) $message->getUid();
			if ($uid <= $lastUid) {
				continue;
			}
			$parsed = self::parseMessage($message);
			$id = Message::insertIfNew((int) $account['id'], $uid, $parsed);
			if ($id) {
				$count++;
			}
			$maxUid = max($maxUid, $uid);
		}

		$client->disconnect();
		if ($maxUid > $lastUid) {
			\App\Db\Db::getInstance()->createCommand()->update('u_yf_mail_accounts', ['last_uid' => $maxUid], ['id' => $account['id']])->execute();
		}
		return $count;
	}

	private static function parseMessage($message): array
	{
		$from = $message->getFrom()->first();
		$to = [];
		foreach ($message->getTo() as $addr) {
			$to[] = ['email' => $addr->mail ?? '', 'name' => $addr->personal ?? ''];
		}
		$cc = [];
		foreach ($message->getCc() as $addr) {
			$cc[] = ['email' => $addr->mail ?? '', 'name' => $addr->personal ?? ''];
		}
		$attachments = [];
		foreach ($message->getAttachments() as $att) {
			$attachments[] = [
				'name' => $att->name,
				'mime' => $att->content_type ?? 'application/octet-stream',
				'content' => $att->content,
				'content_id' => $att->id ?? null,
			];
		}
		return [
			'message_id' => $message->getMessageId(),
			'in_reply_to' => $message->getInReplyTo(),
			'references' => implode(' ', (array) $message->getReferences()),
			'date_sent' => self::formatMessageDate($message->getDate()),
			'from_email' => $from->mail ?? '',
			'from_name' => $from->personal ?? null,
			'to' => $to,
			'cc' => $cc,
			'subject' => $message->getSubject() ?? '',
			'body_html' => $message->getHTMLBody(),
			'body_text' => $message->getTextBody(),
			'attachments' => $attachments,
			'size_bytes' => strlen($message->getRawBody() ?? ''),
		];
	}

	private static function formatMessageDate(mixed $dateAttr): string
	{
		if ($dateAttr === null) {
			return date('Y-m-d H:i:s');
		}
		try {
			if ($dateAttr instanceof \Webklex\PHPIMAP\Attribute) {
				return $dateAttr->toDate()->format('Y-m-d H:i:s');
			}
			if ($dateAttr instanceof \DateTimeInterface) {
				return $dateAttr->format('Y-m-d H:i:s');
			}
		} catch (\Throwable) {
			return date('Y-m-d H:i:s');
		}

		return date('Y-m-d H:i:s');
	}
}
