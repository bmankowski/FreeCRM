<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * Coordinates staging/import workflow for ImportManager batches.
 */

declare(strict_types=1);

namespace App\Modules\ImportManager\Services;

use App\Modules\Base\Models\Module as ModuleModel;

class BatchProcessor
{
	private BatchRepository $batches;
	private MappingRepository $mappings;
	private ConfigProvider $config;
	private StagingWriter $stagingWriter;
	private RecordPersister $recordPersister;
	private TemporaryTableManager $tables;

	public function __construct(
		?BatchRepository $batches = null,
		?MappingRepository $mappings = null,
		?ConfigProvider $config = null,
		?StagingWriter $stagingWriter = null,
		?RecordPersister $recordPersister = null,
		?TemporaryTableManager $tables = null
	) {
		$this->batches = $batches ?? new BatchRepository();
		$this->mappings = $mappings ?? new MappingRepository();
		$this->config = $config ?? new ConfigProvider();
		$this->stagingWriter = $stagingWriter ?? new StagingWriter($this->config);
		$this->recordPersister = $recordPersister ?? new RecordPersister(null, null, null, null, $this->config);
		$this->tables = $tables ?? new TemporaryTableManager();
	}

	public function stage(int $batchId): array
	{
		$batch = $this->batches->find($batchId);
		if (!$batch) {
			throw new \RuntimeException('Nie znaleziono wskazanego wsadu.');
		}

		$module = ModuleModel::getInstance($batch['module']);
		if (!$module) {
			throw new \RuntimeException('Nie można zainicjować modułu docelowego.');
		}

		$mappingRow = $this->mappings->findByBatch($batchId);
		if (!$mappingRow) {
			throw new \RuntimeException('Brakuje zdefiniowanego mapowania dla wsadu.');
		}

		$definition = MappingDefinition::fromDatabaseRow(
			$mappingRow,
			$module,
			$this->config,
			$batch['duplicate_strategy'] ?? null
		);
		$result = $this->stagingWriter->stage($batch, $module, $definition);

		$this->batches->update($batchId, [
			'status' => 'staged',
			'total_rows' => $result['total'],
			'processed_rows' => $result['total'] - $result['failed'],
			'error_rows' => $result['failed'],
		]);

		return $result;
	}

	public function import(int $batchId): array
	{
		$context = $this->buildContext($batchId);
		$this->batches->update($batchId, [
			'status' => 'running',
			'started_at' => date('Y-m-d H:i:s'),
		]);

		try {
			$result = $this->recordPersister->persist($context);
			$this->batches->update($batchId, [
				'status' => 'completed',
				'processed_rows' => $result['created'] + $result['updated'],
				'error_rows' => $result['failed'],
				'finished_at' => date('Y-m-d H:i:s'),
			]);
			return $result;
		} catch (\Throwable $exception) {
			$this->batches->update($batchId, [
				'status' => 'failed',
				'notes' => $exception->getMessage(),
				'finished_at' => date('Y-m-d H:i:s'),
			]);
			throw $exception;
		}
	}

	private function buildContext(int $batchId): array
	{
		$batch = $this->batches->find($batchId);
		if (!$batch) {
			throw new \RuntimeException('Nie znaleziono wskazanego wsadu.');
		}

		$module = ModuleModel::getInstance($batch['module']);
		if (!$module) {
			throw new \RuntimeException('Nie można zainicjować modułu docelowego.');
		}

		$mappingRow = $this->mappings->findByBatch($batchId);
		if (!$mappingRow) {
			throw new \RuntimeException('Brakuje zdefiniowanego mapowania dla wsadu.');
		}

		$definition = MappingDefinition::fromDatabaseRow(
			$mappingRow,
			$module,
			$this->config,
			$batch['duplicate_strategy'] ?? null
		);

		$tableName = $this->tables->getTableName($module->getName(), $batchId);
		if (!(bool) $this->db()->getTableSchema($tableName, true)) {
			throw new \RuntimeException('Tabela staging nie istnieje – przygotuj dane ponownie.');
		}

		return [
			'batch' => $batch,
			'module' => $module,
			'definition' => $definition,
			'table' => $tableName,
		];
	}

	private function db(): \yii\db\Connection
	{
		return \App\Db\Db::getInstance();
	}
}

