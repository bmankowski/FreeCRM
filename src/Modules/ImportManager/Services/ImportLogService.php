<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * Persists import logs both in database and json file.
 */

declare(strict_types=1);

namespace App\Modules\ImportManager\Services;

class ImportLogService
{
	private BatchRepository $batches;
	private \yii\db\Connection $db;

	public function __construct(?BatchRepository $batches = null, ?\yii\db\Connection $db = null)
	{
		$this->batches = $batches ?? new BatchRepository();
		$this->db = $db ?? \App\Db\Db::getInstance();
	}

	/**
	 * Store log entry.
	 *
	 * @param array<string, mixed> $payload
	 */
	public function log(
		int $batchId,
		string $stage,
		string $status,
		string $message,
		array $payload = [],
		?int $rowNumber = null,
		?int $recordId = null
	): void {
		$entry = [
			'batch_id' => $batchId,
			'row_number' => $rowNumber,
			'record_id' => $recordId,
			'stage' => $stage,
			'status' => $status,
			'message' => $message,
			'payload' => $payload ? \App\Utils\Json::encode($payload) : null,
			'created_at' => date('Y-m-d H:i:s'),
		];

		$this->db->createCommand()->insert('#__import_logs', $entry)->execute();
		$this->appendToFile($batchId, $entry);
	}

	/**
	 * Append log entry to batch json file.
	 *
	 * @param array<string, mixed> $entry
	 */
	private function appendToFile(int $batchId, array $entry): void
	{
		$batch = $this->batches->find($batchId);
		if (!$batch || empty($batch['storage_path'])) {
			return;
		}

		$basePath = ROOT_DIRECTORY . '/' . ltrim((string) $batch['storage_path'], '/');
		if (!is_dir($basePath) && !@mkdir($basePath, 0775, true) && !is_dir($basePath)) {
			return;
		}

		$filePath = rtrim($basePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'log.json';
		$handle = @fopen($filePath, 'ab');
		if (!$handle) {
			return;
		}

		$line = \App\Utils\Json::encode([
			'timestamp' => $entry['created_at'],
			'stage' => $entry['stage'],
			'status' => $entry['status'],
			'message' => $entry['message'],
			'row' => $entry['row_number'],
			'record' => $entry['record_id'],
			'payload' => $entry['payload'] ? \App\Utils\Json::decode($entry['payload'], true) : null,
		]);
		fwrite($handle, $line . PHP_EOL);
		fclose($handle);
	}
}

