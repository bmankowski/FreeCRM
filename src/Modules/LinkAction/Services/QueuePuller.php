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

namespace App\Modules\LinkAction\Services;

final class QueuePuller
{
	private QueueHttpClient $httpClient;

	public function __construct(?QueueHttpClient $httpClient = null)
	{
		$this->httpClient = $httpClient ?? new QueueHttpClient();
	}

	public function fetch(): bool
	{
		$config = $this->getConfig();
		if ($config === null) {
			return false;
		}

		FilePaths::ensureDirectories();
		$localPath = FilePaths::incomingQueueFile();
		$localDir = dirname($localPath);
		if (!is_dir($localDir) && !mkdir($localDir, 0755, true) && !is_dir($localDir)) {
			throw new \RuntimeException('Could not create incoming directory: ' . $localDir);
		}

		$fetchUrl = (string) $config['fetch_url'];
		$apiKey = (string) $config['api_key'];
		$timeout = (int) ($config['timeout_seconds'] ?? 30);

		$response = $this->httpClient->request('GET', $fetchUrl, $apiKey, $timeout);
		if ($response['error'] !== '') {
			\App\Log\Log::error('LinkAction queue fetch failed: ' . $response['error']);
			return false;
		}

		if ($response['status'] === 204) {
			\App\Log\Log::trace('LinkAction queue fetch: remote queue empty');
			return false;
		}

		if ($response['status'] === 404) {
			\App\Log\Log::error('LinkAction queue fetch failed: unauthorized or endpoint missing');
			return false;
		}

		if ($response['status'] !== 200) {
			\App\Log\Log::error('LinkAction queue fetch failed: HTTP ' . $response['status']);
			return false;
		}

		$body = trim($response['body']);
		if ($body === '') {
			return false;
		}

		$tempPath = $localPath . '.tmp.' . getmypid();
		@unlink($tempPath);
		if (file_put_contents($tempPath, $body . "\n") === false) {
			@unlink($tempPath);
			throw new \RuntimeException('LinkAction queue fetch failed: could not write temp queue file');
		}

		return $this->mergeFetchedQueue($localPath, $tempPath);
	}

	public function ack(): bool
	{
		$config = $this->getConfig();
		if ($config === null) {
			return false;
		}

		$ackUrl = (string) ($config['ack_url'] ?? $config['fetch_url'] ?? '');
		$apiKey = (string) $config['api_key'];
		$timeout = (int) ($config['timeout_seconds'] ?? 30);
		if ($ackUrl === '') {
			\App\Log\Log::error('LinkAction queue ack skipped: ack_url missing');
			return false;
		}

		$response = $this->httpClient->request('POST', $ackUrl, $apiKey, $timeout);
		if ($response['error'] !== '') {
			\App\Log\Log::error('LinkAction queue ack failed: ' . $response['error']);
			return false;
		}

		if ($response['status'] === 204) {
			\App\Log\Log::trace('LinkAction queue ack succeeded');
			return true;
		}

		\App\Log\Log::error('LinkAction queue ack failed: HTTP ' . $response['status']);
		return false;
	}

	/**
	 * @return array<string, mixed>|null
	 */
	private function getConfig(): ?array
	{
		$config = LinkActionConfig::get('queue_api');
		if (!is_array($config)) {
			\App\Log\Log::error('LinkAction queue fetch skipped: queue_api config missing');
			return null;
		}

		$fetchUrl = (string) ($config['fetch_url'] ?? '');
		$apiKey = (string) ($config['api_key'] ?? '');
		if ($fetchUrl === '' || $apiKey === '') {
			\App\Log\Log::error('LinkAction queue fetch skipped: incomplete queue_api config');
			return null;
		}

		return $config;
	}

	private function mergeFetchedQueue(string $localPath, string $tempPath): bool
	{
		if (!is_readable($tempPath) || filesize($tempPath) === 0) {
			@unlink($tempPath);
			return false;
		}

		if (is_readable($localPath)) {
			$this->appendLocalQueue($localPath, $tempPath);
			@unlink($tempPath);
		} elseif (!rename($tempPath, $localPath)) {
			@unlink($tempPath);
			throw new \RuntimeException('LinkAction queue fetch failed: could not move temp queue file');
		}

		return true;
	}

	private function appendLocalQueue(string $localPath, string $tempPath): void
	{
		$incoming = (string) file_get_contents($tempPath);
		if ($incoming === '') {
			return;
		}
		file_put_contents($localPath, $incoming, FILE_APPEND | LOCK_EX);
	}
}
