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

class Attachment
{
	public static function storeAll(int $messageId, int $accountId, array $attachments): void
	{
		$baseDir = ROOT_DIRECTORY . 'storage/Mail/' . date('Y/m') . '/' . $accountId . '/' . $messageId . '/';
		if (!is_dir($baseDir)) {
			mkdir($baseDir, 0755, true);
		}
		foreach ($attachments as $att) {
			$safeName = preg_replace('/[^A-Za-z0-9._-]/', '_', (string) ($att['name'] ?? 'file'));
			$path = $baseDir . $safeName;
			file_put_contents($path, $att['content'] ?? '');
			$relPath = 'storage/Mail/' . date('Y/m') . '/' . $accountId . '/' . $messageId . '/' . $safeName;
			\App\Db\Db::getInstance()->createCommand()->insert('u_yf_mail_attachments', [
				'message_id' => $messageId,
				'filename' => $safeName,
				'original_name' => $att['name'] ?? $safeName,
				'mime_type' => $att['mime'] ?? 'application/octet-stream',
				'size_bytes' => strlen($att['content'] ?? ''),
				'content_id' => $att['content_id'] ?? null,
				'storage_path' => $relPath,
			])->execute();
		}
	}

	public static function getForMessage(int $messageId): array
	{
		return (new \App\Db\Query())->from('u_yf_mail_attachments')->where(['message_id' => $messageId])->all();
	}
}
