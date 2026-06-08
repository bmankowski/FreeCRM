<?php
declare(strict_types=1);

namespace FreeCRM\LinkAction\Www;

final class RateLimit
{
	public static function exceeded(array $config, string $settingsKey = 'rate_limit'): bool
	{
		$settings = $config[$settingsKey] ?? null;
		if (!is_array($settings)) {
			return false;
		}
		$window = (int) ($settings['window_seconds'] ?? 60);
		$max = (int) ($settings['max_requests'] ?? 30);
		$storage = (string) ($settings['storage_path'] ?? '');
		if ($storage === '' || $window <= 0 || $max <= 0) {
			return false;
		}
		$ip = (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
		$now = time();
		$data = [];
		if (is_readable($storage)) {
			$raw = file_get_contents($storage);
			$decoded = json_decode((string) $raw, true);
			if (is_array($decoded)) {
				$data = $decoded;
			}
		}
		$bucket = $data[$ip] ?? ['start' => $now, 'count' => 0];
		if (($now - (int) $bucket['start']) >= $window) {
			$bucket = ['start' => $now, 'count' => 0];
		}
		$bucket['count'] = (int) $bucket['count'] + 1;
		$data[$ip] = $bucket;
		@file_put_contents($storage, json_encode($data), LOCK_EX);

		return $bucket['count'] > $max;
	}
}
