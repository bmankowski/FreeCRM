<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * Dispatches long-running ImportManager jobs to vtiger_import_queue.
 */

declare(strict_types=1);

namespace App\Modules\ImportManager\Services;

use App\Modules\ImportManager\Jobs\ImportJob;

class QueueDispatcher
{
	private ConfigProvider $config;
	private \yii\db\Connection $db;

	public function __construct(?ConfigProvider $config = null, ?\yii\db\Connection $db = null)
	{
		$this->config = $config ?? new ConfigProvider();
		$this->db = $db ?? \App\Db\Db::getInstance();
	}

	public function shouldEnqueue(array $batch): bool
	{
		$thresholds = (array) $this->config->get('queue.thresholds', []);

		if (!empty($thresholds['records'])) {
			$records = (int) ($batch['total_rows'] ?? 0);
			if ($records === 0 && !empty($batch['preview_rows'])) {
				$records = (int) $batch['preview_rows'];
			}
			if ($records >= (int) $thresholds['records']) {
				return true;
			}
		}

		if (!empty($thresholds['fileSizeMb'])) {
			$fileSizeBytes = (int) ($batch['file_size'] ?? 0);
			$fileSizeMb = $fileSizeBytes > 0 ? $fileSizeBytes / (1024 * 1024) : 0;
			if ($fileSizeMb >= (float) $thresholds['fileSizeMb']) {
				return true;
			}
		}

		return false;
	}

	public function enqueueStage(array $batch): ImportJob
	{
		return $this->enqueueJob($batch, [
			'type' => 'stage',
			'batchId' => (int) $batch['id'],
		]);
	}

	public function enqueueImport(array $batch): ImportJob
	{
		return $this->enqueueJob($batch, [
			'type' => 'import',
			'batchId' => (int) $batch['id'],
		]);
	}

	/**
	 * @return ImportJob[]
	 */
	public function fetchPendingJobs(int $limit = 5): array
	{
		$rows = (new \App\Db\Query())
			->from('vtiger_import_queue')
			->where(['temp_status' => ImportJob::STATUS_SCHEDULED])
			->limit($limit)
			->orderBy(['importid' => SORT_ASC])
			->all($this->db);

		return array_map(static fn($row) => ImportJob::fromRow($row), $rows);
	}

	public function markStatus(int $jobId, int $status): void
	{
		$this->db->createCommand()
			->update('vtiger_import_queue', ['temp_status' => $status], ['importid' => $jobId])
			->execute();
	}

	public function delete(int $jobId): void
	{
		$this->db->createCommand()
			->delete('vtiger_import_queue', ['importid' => $jobId])
			->execute();
	}

	private function enqueueJob(array $batch, array $payload): ImportJob
	{
		$this->db->createCommand()->insert('vtiger_import_queue', [
			'userid' => (int) $batch['created_by'],
			'tabid' => \App\Utils\ModuleUtils::getModuleId($batch['module']),
			'field_mapping' => \App\Utils\Json::encode($payload),
			'default_values' => null,
			'merge_type' => null,
			'merge_fields' => null,
			'temp_status' => ImportJob::STATUS_SCHEDULED,
		])->execute();

		$jobId = (int) $this->db->getLastInsertID();
		$row = (new \App\Db\Query())
			->from('vtiger_import_queue')
			->where(['importid' => $jobId])
			->limit(1)
			->one($this->db);

		return ImportJob::fromRow($row);
	}
}

