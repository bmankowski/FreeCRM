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

use App\Modules\LinkAction\Services\Handlers\HandlerInterface;

final class QueueImporter
{
	public function importIncoming(): void
	{
		$lock = new ImportLock();
		if (!$lock->acquire()) {
			\App\Log\Log::trace('LinkAction import skipped: lock already held');
			return;
		}
		try {
			$this->processQueueFile(FilePaths::incomingQueueFile());
		} finally {
			$lock->release();
		}
	}

	private function processQueueFile(string $queuePath): void
	{
		if (!is_readable($queuePath)) {
			return;
		}

		$lines = file($queuePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
		if (!$lines) {
			return;
		}

		$failedLines = [];
		$processedCount = 0;
		$tokenService = new LinkActionToken();

		foreach ($lines as $lineNumber => $line) {
			$decoded = json_decode($line, true);
			if (!is_array($decoded) || empty($decoded['t']) || !is_string($decoded['t'])) {
				$failedLines[] = $line;
				\App\Log\Log::error('LinkAction import invalid queue line ' . ($lineNumber + 1));
				continue;
			}

			$token = $decoded['t'];
			$payload = $tokenService->verify($token);
			if ($payload === null) {
				$failedLines[] = $line;
				\App\Log\Log::error('LinkAction import token verification failed on line ' . ($lineNumber + 1));
				continue;
			}

			$moduleName = (string) ($payload['module'] ?? '');
			$action = (string) ($payload['action'] ?? '');
			$scope = (string) ($payload['scope'] ?? '');
			$jti = (string) ($payload['jti'] ?? '');

			if ($jti === '' || !LinkActionConfig::isActionAllowed($moduleName, $action, $scope)) {
				$failedLines[] = $line;
				\App\Log\Log::error("LinkAction import rejected unregistered action {$moduleName}/{$action}/{$scope}");
				continue;
			}

			if (LinkActionLog::existsByJti($jti)) {
				continue;
			}

			try {
				$this->dispatch($payload);
				LinkActionLog::insert($token, $payload, LinkActionLog::parseQueueTimestamp($decoded['ts'] ?? null));
				++$processedCount;
			} catch (\Throwable $e) {
				$failedLines[] = $line;
				\App\Log\Log::error('LinkAction import handler failed: ' . $e->getMessage());
			}
		}

		$this->archiveQueueFile($queuePath, $failedLines);
		\App\Log\Log::trace("LinkAction import finished: processed={$processedCount}, failed=" . count($failedLines));
	}

	/**
	 * @param array<string, mixed> $payload
	 */
	private function dispatch(array $payload): void
	{
		$moduleName = (string) ($payload['module'] ?? '');
		$action = (string) ($payload['action'] ?? '');
		$scope = (string) ($payload['scope'] ?? '');
		$handlerClass = LinkActionConfig::handlerClass($moduleName, $action);
		if ($handlerClass === null || !class_exists($handlerClass)) {
			throw new \RuntimeException('LinkAction handler not configured for ' . $moduleName . '/' . $action);
		}
		$handler = new $handlerClass();
		if (!$handler instanceof HandlerInterface || !$handler->supports($moduleName, $action, $scope)) {
			throw new \RuntimeException('LinkAction handler does not support ' . $moduleName . '/' . $action . '/' . $scope);
		}
		$handler->handle($payload);
	}

	/**
	 * @param string[] $failedLines
	 */
	private function archiveQueueFile(string $queuePath, array $failedLines): void
	{
		FilePaths::ensureDirectories();
		$stamp = gmdate('Ymd_His');
		$processedPath = FilePaths::processed() . 'queue_' . $stamp . '.jsonl';
		rename($queuePath, $processedPath);

		if ($failedLines) {
			$failedPath = FilePaths::failed() . 'queue_' . $stamp . '.jsonl';
			file_put_contents($failedPath, implode("\n", $failedLines) . "\n");
		}
	}
}
