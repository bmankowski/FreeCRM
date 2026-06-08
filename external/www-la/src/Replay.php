<?php
declare(strict_types=1);

namespace FreeCRM\LinkAction\Www;

final class Replay
{
	public static function seen(string $jti, array $config): bool
	{
		$cachePath = (string) ($config['jti_cache_path'] ?? '');
		if ($cachePath === '' || $jti === '') {
			return false;
		}
		$seen = [];
		if (is_readable($cachePath)) {
			$lines = file($cachePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
			$seen = array_flip($lines);
		}
		if (isset($seen[$jti])) {
			return true;
		}
		@file_put_contents($cachePath, $jti . "\n", FILE_APPEND | LOCK_EX);

		return false;
	}
}
