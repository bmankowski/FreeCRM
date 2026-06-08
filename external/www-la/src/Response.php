<?php
declare(strict_types=1);

namespace FreeCRM\LinkAction\Www;

final class Response
{
	public static function render(string $root, string $responseName): void
	{
		$responseFile = $root . '/responses/' . basename($responseName) . '.php';
		if (!is_readable($responseFile)) {
			$responseFile = $root . '/responses/error.php';
		}
		require $responseFile;
		exit;
	}

	public static function reject(string $root, array $config, string $reason): void
	{
		$logPath = (string) ($config['reject_log_path'] ?? '');
		if ($logPath !== '') {
			$line = date('c') . "\t" . $reason . "\n";
			@file_put_contents($logPath, $line, FILE_APPEND | LOCK_EX);
		}
		self::render($root, 'error');
	}
}
