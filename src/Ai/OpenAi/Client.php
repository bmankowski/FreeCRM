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
 * OpenAI HTTP client (Chat Completions + Models list).
 */
final class Client
{
	private const CHAT_ENDPOINT = 'https://api.openai.com/v1/chat/completions';
	private const MODELS_ENDPOINT = 'https://api.openai.com/v1/models';
	private const TIMEOUT_SECONDS = 45;

	/**
	 * @param list<array{role: string, content: string}> $messages
	 * @throws OpenAiException
	 */
	public function chatCompletions(string $apiKey, string $model, array $messages, RequestContext $context): string
	{
		$apiKey = $this->requireApiKey($apiKey);
		$model = trim($model);
		if ($model === '') {
			throw new OpenAiException('LBL_AI_MODEL_MISSING');
		}

		$payload = json_encode([
			'model' => $model,
			'messages' => $messages,
		], JSON_UNESCAPED_UNICODE);
		if ($payload === false) {
			throw new OpenAiException('LBL_AI_REQUEST_ENCODE_FAILED');
		}

		$decoded = $this->requestJson(
			'POST',
			self::CHAT_ENDPOINT,
			$apiKey,
			$payload,
			$context,
			'chat.completions',
			$model,
			$messages,
			strlen($payload)
		);
		$content = $decoded['choices'][0]['message']['content'] ?? null;
		if (!is_string($content) || trim($content) === '') {
			throw new OpenAiException('LBL_AI_EMPTY_RESPONSE');
		}

		return self::stripCodeFences(trim($content));
	}

	/**
	 * Chat-oriented model ids available to this API key (newest first).
	 *
	 * @return list<string>
	 * @throws OpenAiException
	 */
	public function listChatModels(string $apiKey, RequestContext $context): array
	{
		$apiKey = $this->requireApiKey($apiKey);
		$decoded = $this->requestJson(
			'GET',
			self::MODELS_ENDPOINT,
			$apiKey,
			null,
			$context,
			'models.list',
			'-',
			null,
			0
		);
		$data = $decoded['data'] ?? null;
		if (!is_array($data)) {
			throw new OpenAiException('LBL_AI_INVALID_RESPONSE');
		}

		$scored = [];
		foreach ($data as $row) {
			if (!is_array($row)) {
				continue;
			}
			$id = isset($row['id']) ? (string) $row['id'] : '';
			if ($id === '' || !self::isChatModelId($id)) {
				continue;
			}
			$created = isset($row['created']) ? (int) $row['created'] : 0;
			$scored[] = ['id' => $id, 'created' => $created];
		}

		usort($scored, static function (array $a, array $b): int {
			if ($a['created'] !== $b['created']) {
				return $b['created'] <=> $a['created'];
			}

			return strcmp($a['id'], $b['id']);
		});

		$ids = [];
		foreach ($scored as $item) {
			$ids[] = $item['id'];
		}

		return array_values(array_unique($ids));
	}

	public static function isChatModelId(string $id): bool
	{
		$id = strtolower($id);
		if (preg_match('/(embedding|whisper|tts|dall-e|davinci|babbage|realtime|audio|transcribe|moderation|image|codex|sora)/', $id)) {
			return false;
		}

		return (bool) preg_match('/^(gpt-|o[0-9]|chatgpt-)/', $id);
	}

	/**
	 * @param list<array{role: string, content: string}>|null $messages
	 * @return array<string, mixed>
	 * @throws OpenAiException
	 */
	private function requestJson(
		string $method,
		string $url,
		string $apiKey,
		?string $postBody,
		RequestContext $context,
		string $endpoint,
		string $model,
		?array $messages,
		int $requestBytes
	): array {
		$requestId = AiRequestLogger::newRequestId();
		$started = microtime(true);

		$this->logRequestStart($requestId, $context, $model, $endpoint, $requestBytes, $messages);

		$curl = curl_init();
		if ($curl === false) {
			$this->logResult($requestId, [
				'status' => 'transport',
				'durationMs' => (microtime(true) - $started) * 1000,
				'http' => 0,
				'errno' => 0,
				'responseBytes' => 0,
				'error' => 'curl_init failed',
			]);
			throw new OpenAiException('LBL_AI_HTTP_FAILED');
		}

		$headers = [
			'Authorization: Bearer ' . $apiKey,
			'Accept: application/json',
		];
		$options = [
			CURLOPT_URL => $url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_CUSTOMREQUEST => strtoupper($method),
			CURLOPT_HTTPHEADER => $headers,
			CURLOPT_TIMEOUT => self::TIMEOUT_SECONDS,
			CURLOPT_CONNECTTIMEOUT => 15,
		];
		if ($postBody !== null) {
			$headers[] = 'Content-Type: application/json';
			$options[CURLOPT_HTTPHEADER] = $headers;
			$options[CURLOPT_POSTFIELDS] = $postBody;
		}
		curl_setopt_array($curl, $options);

		$body = curl_exec($curl);
		$errno = curl_errno($curl);
		$status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
		curl_close($curl);
		$durationMs = (microtime(true) - $started) * 1000;
		$responseBytes = is_string($body) ? strlen($body) : 0;

		if ($errno !== 0 || $body === false) {
			$logStatus = AiRequestLogger::statusFromHttpResult($errno, $status, true);
			$this->logResult($requestId, [
				'status' => $logStatus,
				'durationMs' => $durationMs,
				'http' => $status,
				'errno' => $errno,
				'responseBytes' => $responseBytes,
				'error' => 'curl errno=' . $errno,
			]);
			\App\Log\Log::error('OpenAI HTTP transport error: errno=' . $errno);
			throw new OpenAiException('LBL_AI_HTTP_FAILED');
		}

		$decoded = json_decode($body, true);
		if (!is_array($decoded)) {
			$this->logResult($requestId, [
				'status' => 'invalid_json',
				'durationMs' => $durationMs,
				'http' => $status,
				'errno' => 0,
				'responseBytes' => $responseBytes,
				'error' => 'invalid JSON, HTTP ' . $status,
			]);
			\App\Log\Log::error('OpenAI invalid JSON response, HTTP ' . $status);
			throw new OpenAiException('LBL_AI_INVALID_RESPONSE');
		}

		if ($status < 200 || $status >= 300) {
			$apiMessage = self::sanitizeApiErrorMessage(
				(string) ($decoded['error']['message'] ?? ('HTTP ' . $status))
			);
			$this->logResult($requestId, [
				'status' => AiRequestLogger::statusFromHttpResult(0, $status, true),
				'durationMs' => $durationMs,
				'http' => $status,
				'errno' => 0,
				'responseBytes' => $responseBytes,
				'error' => $apiMessage,
				'usage' => is_array($decoded['usage'] ?? null) ? $decoded['usage'] : null,
			]);
			\App\Log\Log::error('OpenAI API error HTTP ' . $status . ': ' . mb_substr($apiMessage, 0, 200));
			if ($status === 401 || $status === 403) {
				throw new OpenAiException('LBL_AI_API_KEY_REJECTED', $apiMessage, $status);
			}
			throw new OpenAiException('LBL_AI_API_ERROR', $apiMessage, $status);
		}

		$content = '';
		$modelsCount = null;
		if ($endpoint === 'chat.completions') {
			$rawContent = $decoded['choices'][0]['message']['content'] ?? '';
			$content = is_string($rawContent) ? $rawContent : '';
		} elseif ($endpoint === 'models.list') {
			$data = $decoded['data'] ?? [];
			$modelsCount = 0;
			if (is_array($data)) {
				foreach ($data as $row) {
					if (is_array($row) && self::isChatModelId((string) ($row['id'] ?? ''))) {
						++$modelsCount;
					}
				}
			}
		}

		$this->logResult($requestId, [
			'status' => 'ok',
			'durationMs' => $durationMs,
			'http' => $status,
			'errno' => 0,
			'responseBytes' => $responseBytes,
			'content' => $endpoint === 'chat.completions' ? $content : null,
			'modelsCount' => $modelsCount,
			'usage' => is_array($decoded['usage'] ?? null) ? $decoded['usage'] : null,
		]);

		return $decoded;
	}

	/**
	 * @param list<array{role: string, content: string}>|null $messages
	 */
	private function logRequestStart(
		string $requestId,
		RequestContext $context,
		string $model,
		string $endpoint,
		int $requestBytes,
		?array $messages
	): void {
		$exchange = [
			'phase' => 'start',
			'id' => $requestId,
			'action' => $context->action,
			'userId' => $context->userId,
			'model' => $model,
			'endpoint' => $endpoint,
			'requestBytes' => $requestBytes,
		];
		if ($messages !== null) {
			$exchange['messages'] = $messages;
		}
		AiRequestLogger::writeExchange($exchange);
	}

	/**
	 * @param array{
	 *   status: string,
	 *   durationMs: float,
	 *   http: int,
	 *   errno: int,
	 *   responseBytes: int,
	 *   content?: ?string,
	 *   error?: string,
	 *   usage?: ?array,
	 *   modelsCount?: ?int
	 * } $result
	 */
	private function logResult(string $requestId, array $result): void
	{
		$exchange = [
			'phase' => 'result',
			'id' => $requestId,
			'status' => $result['status'],
			'durationMs' => $result['durationMs'],
			'http' => $result['http'],
			'errno' => $result['errno'],
			'responseBytes' => $result['responseBytes'],
		];
		if (array_key_exists('content', $result) && $result['content'] !== null) {
			$exchange['content'] = $result['content'];
		}
		if (!empty($result['error'])) {
			$exchange['error'] = (string) $result['error'];
		}
		if (!empty($result['usage']) && is_array($result['usage'])) {
			$exchange['usage'] = $result['usage'];
		}
		if (array_key_exists('modelsCount', $result) && $result['modelsCount'] !== null) {
			$exchange['modelsCount'] = (int) $result['modelsCount'];
		}

		AiRequestLogger::writeExchange($exchange);
	}

	private static function sanitizeApiErrorMessage(string $message): string
	{
		$message = trim($message);
		$message = preg_replace('/sk-[A-Za-z0-9_\-]{8,}/', 'sk-***', $message) ?? $message;

		return mb_substr($message, 0, 300);
	}

	/**
	 * @throws OpenAiException
	 */
	private function requireApiKey(string $apiKey): string
	{
		$apiKey = trim($apiKey);
		if ($apiKey === '') {
			throw new OpenAiException('LBL_AI_API_KEY_MISSING');
		}

		return $apiKey;
	}

	public static function stripCodeFences(string $content): string
	{
		if (preg_match('/^```(?:html)?\s*\n?(.*?)\n?```$/is', $content, $m)) {
			return trim($m[1]);
		}

		return $content;
	}
}
