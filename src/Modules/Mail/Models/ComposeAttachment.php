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

class ComposeAttachment
{
	private const TOKEN_PATTERN = '/^[a-f0-9]{32}$/';

	public static function stagingRoot(): string
	{
		return ROOT_DIRECTORY . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'mail-compose' . DIRECTORY_SEPARATOR;
	}

	public static function userDir(int $userId): string
	{
		return self::stagingRoot() . $userId . DIRECTORY_SEPARATOR;
	}

	/**
	 * @return array{token: string, name: string, size: int}
	 */
	public static function stageUpload(int $userId, array $fileDetails): array
	{
		$fileInstance = \App\Fields\File::loadFromRequest($fileDetails);
		if (!$fileInstance->validate()) {
			throw new \App\Exceptions\AppException('LBL_MAIL_ATTACHMENT_INVALID');
		}
		$originalName = \App\Fields\File::sanitizeUploadFileName($fileDetails['name'] ?? '');
		if (self::isBlockedExtension($originalName)) {
			throw new \App\Exceptions\AppException('LBL_MAIL_ATTACHMENT_BLOCKED_TYPE');
		}
		$size = (int) ($fileDetails['size'] ?? 0);
		$maxFileBytes = self::maxFileBytes();
		if ($size > $maxFileBytes) {
			throw new \App\Exceptions\AppException('LBL_MAIL_ATTACHMENT_TOO_LARGE');
		}

		$token = bin2hex(random_bytes(16));
		$dir = self::ensureUserDir($userId);

		$safeName = preg_replace('/[^A-Za-z0-9._-]/', '_', $originalName) ?: 'file';
		$destPath = $dir . $token . '_' . $safeName;
		$tmpName = (string) ($fileDetails['tmp_name'] ?? '');
		if (!move_uploaded_file($tmpName, $destPath)) {
			\App\Log\Log::error(
				'Compose attachment move failed: tmp=' . $tmpName
				. ' dest=' . $destPath
				. ' is_uploaded=' . (is_uploaded_file($tmpName) ? '1' : '0'),
				'Mail'
			);
			throw new \App\Exceptions\AppException('LBL_MAIL_ATTACHMENT_UPLOAD_FAILED');
		}

		self::writeMeta($userId, $token, [
			'name' => $originalName,
			'size' => $size,
			'created' => time(),
			'file' => $safeName,
		]);

		return ['token' => $token, 'name' => $originalName, 'size' => $size];
	}

	public static function deleteToken(int $userId, string $token): void
	{
		if (!preg_match(self::TOKEN_PATTERN, $token)) {
			return;
		}
		$path = self::tokenPath($userId, $token);
		if ($path !== null && is_file($path)) {
			unlink($path);
		}
		$metaPath = self::metaPath($userId, $token);
		if (is_file($metaPath)) {
			unlink($metaPath);
		}
	}

	/**
	 * @param list<string> $tokens
	 */
	public static function deleteTokens(int $userId, array $tokens): void
	{
		foreach ($tokens as $token) {
			if (is_string($token) && $token !== '') {
				self::deleteToken($userId, $token);
			}
		}
	}

	public static function clearUserStaging(int $userId): void
	{
		$dir = self::userDir($userId);
		if (!is_dir($dir)) {
			return;
		}
		foreach (glob($dir . '*.meta') ?: [] as $metaFile) {
			$token = basename($metaFile, '.meta');
			self::deleteToken($userId, $token);
		}
		if (is_dir($dir) && count(glob($dir . DIRECTORY_SEPARATOR . '*') ?: []) === 0) {
			rmdir($dir);
		}
	}

	/**
	 * @param list<string> $tokens
	 * @return array<string, string>
	 */
	public static function resolveTokens(int $userId, array $tokens): array
	{
		$resolved = [];
		foreach ($tokens as $token) {
			if (!is_string($token) || !preg_match(self::TOKEN_PATTERN, $token)) {
				continue;
			}
			$path = self::tokenPath($userId, $token);
			if ($path === null || !is_file($path)) {
				continue;
			}
			$meta = self::readMeta($userId, $token);
			$displayName = (string) ($meta['name'] ?? basename($path));
			$resolved[realpath($path) ?: $path] = $displayName;
		}

		return $resolved;
	}

	public static function countUserTokens(int $userId): int
	{
		$dir = self::userDir($userId);
		if (!is_dir($dir)) {
			return 0;
		}
		$count = 0;
		foreach (glob($dir . '*.meta') ?: [] as $metaFile) {
			$count++;
		}

		return $count;
	}

	public static function totalUserBytes(int $userId): int
	{
		$dir = self::userDir($userId);
		if (!is_dir($dir)) {
			return 0;
		}
		$total = 0;
		foreach (glob($dir . '*.meta') ?: [] as $metaFile) {
			$meta = json_decode((string) file_get_contents($metaFile), true);
			if (is_array($meta)) {
				$total += (int) ($meta['size'] ?? 0);
			}
		}

		return $total;
	}

	public static function cleanupExpired(): void
	{
		$ttlMinutes = (int) (\App\Core\AppConfig::module('Mail', 'compose_upload_ttl_minutes') ?? 60);
		$cutoff = time() - ($ttlMinutes * 60);
		$root = self::stagingRoot();
		if (!is_dir($root)) {
			return;
		}
		foreach (glob($root . '*', GLOB_ONLYDIR) ?: [] as $userDir) {
			foreach (glob($userDir . DIRECTORY_SEPARATOR . '*.meta') ?: [] as $metaFile) {
				$meta = json_decode((string) file_get_contents($metaFile), true);
				$created = is_array($meta) ? (int) ($meta['created'] ?? 0) : 0;
				if ($created > 0 && $created < $cutoff) {
					$token = basename($metaFile, '.meta');
					$userId = (int) basename($userDir);
					self::deleteToken($userId, $token);
				}
			}
			if (is_dir($userDir) && count(glob($userDir . DIRECTORY_SEPARATOR . '*') ?: []) === 0) {
				rmdir($userDir);
			}
		}
	}

	/**
	 * @return list<array{id: int, name: string}>
	 */
	public static function getTemplateAttachmentMeta(int $templateId): array
	{
		$ids = (new \App\Db\Query())
			->select(['u_#__documents_emailtemplates.crmid'])
			->from('u_#__documents_emailtemplates')
			->innerJoin('vtiger_crmentity', 'u_#__documents_emailtemplates.relcrmid = vtiger_crmentity.crmid')
			->where(['vtiger_crmentity.deleted' => 0, 'u_#__documents_emailtemplates.relcrmid' => $templateId])
			->column();
		if ($ids === []) {
			return [];
		}
		$rows = (new \App\Db\Query())
			->select(['crmid', 'notes_title'])
			->from('vtiger_notes')
			->innerJoin('vtiger_crmentity', 'vtiger_notes.notesid = vtiger_crmentity.crmid')
			->where(['vtiger_crmentity.deleted' => 0, 'vtiger_notes.notesid' => $ids])
			->all();
		$out = [];
		foreach ($rows as $row) {
			$out[] = [
				'id' => (int) $row['crmid'],
				'name' => (string) ($row['notes_title'] ?? 'Document'),
			];
		}

		return $out;
	}

	public static function maxFileBytes(): int
	{
		$mb = (int) (\App\Core\AppConfig::module('Mail', 'attachment_max_size_mb') ?? 25);

		return $mb * 1024 * 1024;
	}

	public static function maxTotalBytes(): int
	{
		$mb = (int) (\App\Core\AppConfig::module('Mail', 'compose_max_total_mb') ?? 100);

		return $mb * 1024 * 1024;
	}

	public static function maxFiles(): int
	{
		return (int) (\App\Core\AppConfig::module('Mail', 'compose_max_files') ?? 10);
	}

	private static function ensureUserDir(int $userId): string
	{
		$dir = self::userDir($userId);
		if (is_dir($dir)) {
			return $dir;
		}
		$root = self::stagingRoot();
		if (!is_dir($root) && !mkdir($root, 0755, true) && !is_dir($root)) {
			\App\Log\Log::error(
				'Compose attachment staging root mkdir failed: path=' . $root
				. ' writable=' . (is_writable(dirname(rtrim($root, DIRECTORY_SEPARATOR))) ? '1' : '0'),
				'Mail'
			);
			throw new \App\Exceptions\AppException('LBL_MAIL_ATTACHMENT_UPLOAD_FAILED');
		}
		if (!mkdir($dir, 0755, true) && !is_dir($dir)) {
			\App\Log\Log::error(
				'Compose attachment user dir mkdir failed: path=' . $dir
				. ' parent_writable=' . (is_writable($root) ? '1' : '0'),
				'Mail'
			);
			throw new \App\Exceptions\AppException('LBL_MAIL_ATTACHMENT_UPLOAD_FAILED');
		}

		return $dir;
	}

	private static function isBlockedExtension(string $filename): bool
	{
		$ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
		$blocked = ['php', 'phtml', 'phar', 'php3', 'php4', 'php5', 'php7', 'php8', 'cgi', 'pl', 'exe', 'sh', 'bat', 'cmd'];

		return in_array($ext, $blocked, true);
	}

	private static function tokenPath(int $userId, string $token): ?string
	{
		$meta = self::readMeta($userId, $token);
		if ($meta === null) {
			return null;
		}
		$file = (string) ($meta['file'] ?? '');
		if ($file === '') {
			return null;
		}
		$path = self::userDir($userId) . $token . '_' . $file;
		if (!is_file($path)) {
			return null;
		}
		$rootReal = realpath(self::stagingRoot());
		$pathReal = realpath($path);
		if ($rootReal === false || $pathReal === false || !str_starts_with($pathReal, $rootReal)) {
			return null;
		}

		return $pathReal;
	}

	private static function metaPath(int $userId, string $token): string
	{
		return self::userDir($userId) . $token . '.meta';
	}

	/**
	 * @return array<string, mixed>|null
	 */
	private static function readMeta(int $userId, string $token): ?array
	{
		$metaPath = self::metaPath($userId, $token);
		if (!is_file($metaPath)) {
			return null;
		}
		$data = json_decode((string) file_get_contents($metaPath), true);

		return is_array($data) ? $data : null;
	}

	/**
	 * @param array<string, mixed> $meta
	 */
	private static function writeMeta(int $userId, string $token, array $meta): void
	{
		$metaPath = self::metaPath($userId, $token);
		file_put_contents($metaPath, json_encode($meta, JSON_UNESCAPED_UNICODE));
	}
}
