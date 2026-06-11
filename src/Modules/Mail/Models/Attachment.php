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
		$baseDir = self::ensureMessageStorageDir($accountId, $messageId);
		if ($baseDir === null) {
			return;
		}
		foreach ($attachments as $att) {
			$safeName = preg_replace('/[^A-Za-z0-9._-]/', '_', (string) ($att['name'] ?? 'file'));
			$path = $baseDir . $safeName;
			file_put_contents($path, $att['content'] ?? '');
			$relPath = self::messageStorageRelPath($accountId, $messageId, $safeName);
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

	/**
	 * @param array<string, mixed> $attachments
	 * @return array<string, string>
	 */
	public static function resolveForSend(array $attachments): array
	{
		if (isset($attachments['attachments']) && is_array($attachments['attachments'])) {
			$nested = $attachments['attachments'];
			unset($attachments['attachments']);
			$attachments = array_merge($attachments, $nested);
		}
		$result = [];
		if (isset($attachments['ids'])) {
			$ids = $attachments['ids'];
			unset($attachments['ids']);
			$result = array_merge($result, \App\Email\Mail::getAttachmentsFromDocument($ids));
		}
		foreach ($attachments as $path => $name) {
			if (is_numeric($path)) {
				$path = $name;
				$name = '';
			}
			if (!is_string($path) || $path === '') {
				continue;
			}
			$pathReal = realpath($path);
			if ($pathReal !== false && is_file($pathReal)) {
				$displayName = is_string($name) && $name !== '' ? $name : basename($pathReal);
				$result[$pathReal] = $displayName;
			}
		}

		return $result;
	}

	/**
	 * @param array<string, string> $pathMap absolute path => display name
	 */
	public static function storeFromPaths(int $messageId, int $accountId, array $pathMap): void
	{
		if ($pathMap === []) {
			return;
		}
		$baseDir = self::ensureMessageStorageDir($accountId, $messageId);
		if ($baseDir === null) {
			return;
		}
		$db = \App\Db\Db::getInstance();
		foreach ($pathMap as $absPath => $displayName) {
			if (!is_file($absPath)) {
				continue;
			}
			$safeName = preg_replace('/[^A-Za-z0-9._-]/', '_', (string) $displayName) ?: 'file';
			$destPath = $baseDir . $safeName;
			if ($absPath !== $destPath) {
				if (!copy($absPath, $destPath)) {
					continue;
				}
			}
			$mime = mime_content_type($destPath) ?: 'application/octet-stream';
			$relPath = self::messageStorageRelPath($accountId, $messageId, $safeName);
			$db->createCommand()->insert('u_yf_mail_attachments', [
				'message_id' => $messageId,
				'filename' => $safeName,
				'original_name' => $displayName,
				'mime_type' => $mime,
				'size_bytes' => filesize($destPath) ?: 0,
				'content_id' => null,
				'storage_path' => $relPath,
			])->execute();
		}
		Message::updateOutbound($messageId, ['has_attachments' => 1]);
	}

	private static function messageStorageDir(int $accountId, int $messageId): string
	{
		return ROOT_DIRECTORY . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'Mail'
			. DIRECTORY_SEPARATOR . date('Y/m') . DIRECTORY_SEPARATOR . $accountId . DIRECTORY_SEPARATOR . $messageId . DIRECTORY_SEPARATOR;
	}

	private static function messageStorageRelPath(int $accountId, int $messageId, string $safeName): string
	{
		return 'storage/Mail/' . date('Y/m') . '/' . $accountId . '/' . $messageId . '/' . $safeName;
	}

	private static function ensureMessageStorageDir(int $accountId, int $messageId): ?string
	{
		$baseDir = self::messageStorageDir($accountId, $messageId);
		if (is_dir($baseDir)) {
			return $baseDir;
		}
		$mailRoot = ROOT_DIRECTORY . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'Mail' . DIRECTORY_SEPARATOR;
		if (!is_dir($mailRoot) && !mkdir($mailRoot, 0755, true) && !is_dir($mailRoot)) {
			\App\Log\Log::error('Mail attachment storage root mkdir failed: path=' . $mailRoot, 'Mail');
			return null;
		}
		if (!mkdir($baseDir, 0755, true) && !is_dir($baseDir)) {
			\App\Log\Log::error('Mail attachment storage dir mkdir failed: path=' . $baseDir, 'Mail');
			return null;
		}

		return $baseDir;
	}
}
