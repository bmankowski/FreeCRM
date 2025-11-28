<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * Persists duplicate-handling configuration for ImportManager batches.
 */

declare(strict_types=1);

namespace App\Modules\ImportManager\Actions;

use App\Base\Controllers\BaseActionController;
use App\Modules\ImportManager\Services\BatchRepository;
use App\Modules\ImportManager\Services\ConfigProvider;
use App\Modules\ImportManager\Services\MappingDefinition;
use App\Modules\ImportManager\Services\MappingRepository;
use App\Modules\ImportManager\Services\DuplicateRuleRepository;

class SaveDuplicates extends BaseActionController
{
	private ConfigProvider $config;
	private MappingRepository $mappings;
	private BatchRepository $batches;
	private DuplicateRuleRepository $duplicateRules;

	public function __construct()
	{
		parent::__construct();
		$this->config = new ConfigProvider();
		$this->mappings = new MappingRepository();
		$this->batches = new BatchRepository();
		$this->duplicateRules = new DuplicateRuleRepository();
	}

	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		$batchId = (int) $request->get('batch_id');
		if ($batchId <= 0) {
			throw new \App\Exceptions\NoPermitted('LBL_PERMISSION_DENIED');
		}
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$response = new \App\Http\Vtiger_Response();

		try {
			$batchId = (int) $request->get('batch_id');
			$batch = $this->fetchBatch($batchId);
			$this->guardBatchOwnership($batch);

			$module = \App\Modules\Base\Models\Module::getInstance($batch['module']);
			if (!$module) {
				throw new \RuntimeException('Nie udało się zainicjować modułu docelowego.');
			}

			$existingMapping = $this->mappings->findByBatch($batchId);
			if (!$existingMapping) {
				throw new \RuntimeException('Najpierw zapisz mapowanie pól.');
			}

			$sourceHeaders = [];
			if (!empty($existingMapping['options'])) {
				$options = \App\Utils\Json::decode($existingMapping['options']);
				if (is_array($options) && !empty($options['sourceHeaders'])) {
					$sourceHeaders = (array) $options['sourceHeaders'];
				}
			}

			$duplicateSetsPayload = $this->decodeJson($request->get('duplicate_sets'), 'duplicate_sets');
			\App\Log\Log::info('SaveDuplicates - received duplicate_sets: ' . json_encode($duplicateSetsPayload), 'ImportManager');
			
			$payload = [
				'batchId' => $batchId,
				'mapping' => \App\Utils\Json::decode($existingMapping['mapping'] ?? '') ?? [],
				'defaultValues' => \App\Utils\Json::decode($existingMapping['default_values'] ?? '') ?? [],
				'duplicateSets' => $duplicateSetsPayload,
				'sourceHeaders' => $sourceHeaders,
				'duplicateStrategy' => $request->get('duplicate_strategy') ?? $batch['duplicate_strategy'],
			];

			$definition = MappingDefinition::fromPayload($payload, $module, $this->config);
			$savedDuplicateSets = $definition->getDuplicateSets();
			\App\Log\Log::info('SaveDuplicates - saving duplicate sets: ' . json_encode($savedDuplicateSets), 'ImportManager');
			
			$saveResult = $this->mappings->save($definition);
			$this->duplicateRules->save($batch['module'], $savedDuplicateSets['required'] ?? []);

			$this->batches->update($batchId, [
				'status' => 'duplicates_ready',
				'duplicate_strategy' => $definition->getDuplicateStrategy(),
			]);

			$response->setResult([
				'mapping' => $saveResult,
				'duplicate_strategy' => $definition->getDuplicateStrategy(),
			]);
		} catch (\Throwable $exception) {
			\App\Log\Log::error('ImportManager SaveDuplicates failed: ' . $exception->getMessage(), 'ImportManager');
			$response->setError(500, $exception->getMessage());
		}

		$response->emit();
	}

	private function decodeJson($value, string $field)
	{
		if ($value === null || $value === '') {
			return [];
		}
		if (is_array($value)) {
			return $value;
		}
		$decoded = \App\Utils\Json::decode((string) $value);
		if ($decoded === false || $decoded === null) {
			throw new \RuntimeException('Nie można zinterpretować danych pola ' . $field . '.');
		}
		return $decoded;
	}

	private function fetchBatch(int $batchId): array
	{
		$data = (new \App\Db\Query())
			->from('#__import_batches')
			->where(['id' => $batchId])
			->limit(1)
			->one();

		if (!$data) {
			throw new \RuntimeException('Nie znaleziono wsadu importu.');
		}

		return $data;
	}

	private function guardBatchOwnership(array $batch): void
	{
		$currentUserId = \App\Modules\Users\Models\Record::getCurrentUserId();
		if ((int) $batch['created_by'] !== (int) $currentUserId) {
			throw new \RuntimeException('Możesz edytować tylko własne wsady importu.');
		}

		if ($batch['status'] === 'running') {
			throw new \RuntimeException('Nie można zmieniać konfiguracji duplikatów podczas importu.');
		}
	}
}


