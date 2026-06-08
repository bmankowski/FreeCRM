<?php
declare(strict_types=1);

namespace FreeCRM\LinkAction\Www;

final class Queue
{
	public static function append(string $token, array $config): bool
	{
		$queuePath = (string) ($config['queue_path'] ?? '');
		if ($queuePath === '') {
			return false;
		}
		$dir = dirname($queuePath);
		if (!is_dir($dir) && !mkdir($dir, 0750, true) && !is_dir($dir)) {
			return false;
		}
		$line = json_encode([
			'ts' => date('c'),
			't' => $token,
			'fp' => hash('sha256', $token),
		], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

		return file_put_contents($queuePath, $line . "\n", FILE_APPEND | LOCK_EX) !== false;
	}

	/** @return string Empty when missing; null on lock/read failure. */
	public static function read(string $queuePath): ?string
	{
		if ($queuePath === '' || !is_readable($queuePath)) {
			return '';
		}
		$handle = fopen($queuePath, 'rb');
		if ($handle === false) {
			return null;
		}
		if (!flock($handle, LOCK_EX)) {
			fclose($handle);

			return null;
		}
		$contents = stream_get_contents($handle);
		flock($handle, LOCK_UN);
		fclose($handle);
		if ($contents === false) {
			return null;
		}

		return $contents;
	}

	public static function truncate(string $queuePath): bool
	{
		if ($queuePath === '') {
			return false;
		}
		$dir = dirname($queuePath);
		if (!is_dir($dir) && !mkdir($dir, 0750, true) && !is_dir($dir)) {
			return false;
		}
		$handle = fopen($queuePath, 'c+b');
		if ($handle === false) {
			return false;
		}
		if (!flock($handle, LOCK_EX)) {
			fclose($handle);

			return false;
		}
		$ok = ftruncate($handle, 0) !== false;
		fflush($handle);
		flock($handle, LOCK_UN);
		fclose($handle);

		return $ok;
	}
}
