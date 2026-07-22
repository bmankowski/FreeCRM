<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * @project FreeCRM
 * @author bmankowski@gmail.com
 * @copyright (c) FreeCRM
 * @license FreeCRM Public License 1.1
 */

declare(strict_types=1);

namespace App\Ai\OpenAi;

/**
 * Appends OpenAI request/response exchanges to cache/logs/ai.log.
 */
final class AiRequestLogger
{
	/** @internal tests */
	public static ?bool $enabledOverride = null;

	/** @internal tests */
	public static ?string $pathOverride = null;

	public static function isEnabled(): bool
	{
		if (self::$enabledOverride !== null) {
			return self::$enabledOverride;
		}

		return (bool) \App\Core\AppConfig::debug('LOG_AI_REQUESTS');
	}

	public static function logPath(): string
	{
		if (self::$pathOverride !== null && self::$pathOverride !== '') {
			return self::$pathOverride;
		}

		return ROOT_DIRECTORY . '/cache/logs/ai.log';
	}

	public static function newRequestId(): string
	{
		try {
			return bin2hex(random_bytes(16));
		} catch (\Throwable) {
			return str_replace('.', '', uniqid('ai', true));
		}
	}

	/**
	 * Redact data: URIs and API-key-like tokens for log storage only.
	 */
	public static function redactForLog(string $text): string
	{
		$text = preg_replace_callback(
			'/data:[a-z0-9.+\/\-]+;base64,[A-Za-z0-9+\/=]+/i',
			static fn(array $m): string => '[data-uri omitted ' . strlen($m[0]) . ' chars]',
			$text
		) ?? $text;
		$text = preg_replace('/sk-[A-Za-z0-9_\-]{8,}/', 'sk-***', $text) ?? $text;

		return $text;
	}

	/**
	 * @param array{
	 *   id: string,
	 *   action: string,
	 *   userId: ?int,
	 *   model: string,
	 *   endpoint: string,
	 *   requestBytes: int,
	 *   messages?: list<array{role: string, content: string}>,
	 *   status?: string,
	 *   durationMs?: float,
	 *   http?: int,
	 *   errno?: int,
	 *   responseBytes?: int,
	 *   content?: string,
	 *   error?: string,
	 *   usage?: array{prompt_tokens?: int, completion_tokens?: int, total_tokens?: int},
	 *   modelsCount?: int,
	 *   phase?: 'start'|'result'|'full'
	 * } $exchange
	 */
	public static function writeExchange(array $exchange): void
	{
		if (!self::isEnabled()) {
			return;
		}

		try {
			$block = self::formatExchange($exchange);
			$path = self::logPath();
			$dir = dirname($path);
			if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
				throw new \RuntimeException('Cannot create AI log directory: ' . $dir);
			}
			$ok = @file_put_contents($path, $block, FILE_APPEND | LOCK_EX);
			if ($ok === false) {
				throw new \RuntimeException('Cannot append AI log: ' . $path);
			}
		} catch (\Throwable $e) {
			\App\Log\Log::error('AI request log write failed: ' . $e->getMessage());
		}
	}

	/**
	 * @param array<string, mixed> $exchange
	 */
	public static function formatExchange(array $exchange): string
	{
		$id = (string) ($exchange['id'] ?? '');
		$ts = (new \DateTimeImmutable('now'))->format('c');
		$phase = (string) ($exchange['phase'] ?? 'full');
		if (!in_array($phase, ['start', 'result', 'full'], true)) {
			$phase = 'full';
		}
		$user = array_key_exists('userId', $exchange) && $exchange['userId'] !== null
			? (string) (int) $exchange['userId']
			: '-';
		$lines = [];

		if ($phase === 'start' || $phase === 'full') {
			$lines[] = "=== ai.request id={$id} ts={$ts} ===";
			$lines[] = sprintf(
				'action=%s user=%s model=%s endpoint=%s',
				(string) ($exchange['action'] ?? '-'),
				$user,
				(string) ($exchange['model'] ?? '-'),
				(string) ($exchange['endpoint'] ?? '-')
			);
			$lines[] = 'request_bytes=' . (int) ($exchange['requestBytes'] ?? 0);

			$messages = $exchange['messages'] ?? null;
			if (is_array($messages) && $messages !== []) {
				$lines[] = 'messages:';
				foreach ($messages as $msg) {
					if (!is_array($msg)) {
						continue;
					}
					$role = (string) ($msg['role'] ?? '?');
					$content = self::redactForLog((string) ($msg['content'] ?? ''));
					$lines[] = "  [{$role}] {$content}";
				}
			}
			if ($phase === 'start') {
				$lines[] = 'status=started';
				$lines[] = "=== ai.waiting id={$id} ===";
				$lines[] = '';

				return implode("\n", $lines);
			}
		}

		if ($phase === 'result') {
			$lines[] = "=== ai.result id={$id} ts={$ts} ===";
		} else {
			$lines[] = '---';
		}

		$lines[] = sprintf(
			'status=%s duration_ms=%s http=%d errno=%d',
			(string) ($exchange['status'] ?? '-'),
			number_format((float) ($exchange['durationMs'] ?? 0), 2, '.', ''),
			(int) ($exchange['http'] ?? 0),
			(int) ($exchange['errno'] ?? 0)
		);
		$lines[] = 'response_bytes=' . (int) ($exchange['responseBytes'] ?? 0);

		$usage = $exchange['usage'] ?? null;
		if (is_array($usage)) {
			$lines[] = sprintf(
				'usage: prompt_tokens=%s completion_tokens=%s total_tokens=%s',
				isset($usage['prompt_tokens']) ? (string) (int) $usage['prompt_tokens'] : '-',
				isset($usage['completion_tokens']) ? (string) (int) $usage['completion_tokens'] : '-',
				isset($usage['total_tokens']) ? (string) (int) $usage['total_tokens'] : '-'
			);
		}

		if (array_key_exists('modelsCount', $exchange)) {
			$lines[] = 'models_count=' . (int) $exchange['modelsCount'];
		}

		if (array_key_exists('content', $exchange)) {
			$lines[] = 'content:';
			$lines[] = '  ' . self::redactForLog((string) $exchange['content']);
		}

		$error = isset($exchange['error']) ? trim((string) $exchange['error']) : '';
		if ($error !== '') {
			$lines[] = 'error: ' . self::redactForLog($error);
		}

		$lines[] = "=== ai.end id={$id} ===";
		$lines[] = '';

		return implode("\n", $lines);
	}

	public static function statusFromHttpResult(int $errno, int $http, bool $jsonOk): string
	{
		if ($errno === 28) {
			return 'timeout';
		}
		if ($errno !== 0) {
			return 'transport';
		}
		if (!$jsonOk) {
			return 'invalid_json';
		}
		if ($http >= 400 && $http < 500) {
			return 'http_4xx';
		}
		if ($http >= 500) {
			return 'http_5xx';
		}
		if ($http >= 200 && $http < 300) {
			return 'ok';
		}

		return 'http_' . $http;
	}
}
