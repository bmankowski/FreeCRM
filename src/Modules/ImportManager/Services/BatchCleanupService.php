<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * Removes import batches with related staging tables, files and queue jobs.
 */

declare(strict_types=1);

namespace App\Modules\ImportManager\Services;

class BatchCleanupService
{
	private BatchRepository $batches;
	private TemporaryTableManager $tables;
	private QueueDispatcher $queue;

	public function __construct(
		?BatchRepository $batches = null,
		?TemporaryTableManager $tables = null,
		?QueueDispatcher $queue = null
	) {
		$this->batches = $batches ?? new BatchRepository();
		$this->tables = $tables ?? new TemporaryTableManager();
		$this->queue = $queue ?? new QueueDispatcher();
	}

	public function deleteBatch(int $batchId, int $userId): void
	{
		$batch = $this->batches->find($batchId);
		if (!$batch) {
			throw new \RuntimeException('Nie znaleziono wsadu importu.');
		}

		if ((int) $batch['created_by'] !== $userId) {
			throw new \RuntimeException('Możesz usuwać tylko własne wsady importu.');
		}

		if (($batch['status'] ?? '') === 'running') {
			throw new \RuntimeException('Nie można usunąć wsadu podczas aktywnego importu.');
		}

		$this->queue->deleteJobsForBatch($batchId);

		$moduleName = (string) ($batch['module'] ?? '');
		if ($moduleName !== '') {
			$this->tables->drop($this->tables->getTableName($moduleName, $batchId));
		}

		$storagePath = (string) ($batch['storage_path'] ?? '');
		if ($storagePath !== '') {
			$absolutePath = ROOT_DIRECTORY . '/' . ltrim($storagePath, '/');
			if (is_dir($absolutePath)) {
				$this->deleteDirectory($absolutePath);
			}
		}

		$this->batches->delete($batchId);
	}

	private function deleteDirectory(string $path): void
	{
		$iterator = new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS);
		foreach (new \RecursiveIteratorIterator($iterator, \RecursiveIteratorIterator::CHILD_FIRST) as $item) {
			if ($item->isDir()) {
				@rmdir((string) $item);
			} else {
				@unlink((string) $item);
			}
		}
		@rmdir($path);
	}
}
