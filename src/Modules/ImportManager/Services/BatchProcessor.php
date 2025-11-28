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

	public function __construct(
		?BatchRepository $batches = null,
		?MappingRepository $mappings = null,
		?ConfigProvider $config = null,
		?StagingWriter $stagingWriter = null
	) {
		$this->batches = $batches ?? new BatchRepository();
		$this->mappings = $mappings ?? new MappingRepository();
		$this->config = $config ?? new ConfigProvider();
		$this->stagingWriter = $stagingWriter ?? new StagingWriter($this->config);
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
}

