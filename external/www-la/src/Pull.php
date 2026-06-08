<?php
declare(strict_types=1);

namespace FreeCRM\LinkAction\Www;

final class Pull
{
	public static function verifyKey(array $config): bool
	{
		$expected = (string) ($config['pull_api_key'] ?? '');
		if ($expected === '') {
			return false;
		}
		$provided = (string) ($_SERVER['HTTP_X_LINKACTION_PULL_KEY'] ?? '');
		if ($provided === '') {
			return false;
		}

		return hash_equals($expected, $provided);
	}

	public static function log(array $config, string $method, bool $authorized): void
	{
		$logPath = (string) ($config['pull_log_path'] ?? '');
		if ($logPath === '') {
			$queuePath = (string) ($config['queue_path'] ?? '');
			if ($queuePath !== '') {
				$logPath = dirname($queuePath) . '/pull.log';
			}
		}
		if ($logPath === '') {
			return;
		}
		$ip = (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
		$status = $authorized ? 'ok' : 'denied';
		$line = date('c') . "\t" . $method . "\t" . $ip . "\t" . $status . "\n";
		@file_put_contents($logPath, $line, FILE_APPEND | LOCK_EX);
	}

	public static function hide(): void
	{
		http_response_code(404);
		exit;
	}
}
