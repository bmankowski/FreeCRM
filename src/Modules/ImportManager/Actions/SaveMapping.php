<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * Persists field mapping definitions configured in the ImportManager wizard.
 */

declare(strict_types=1);

namespace App\Modules\ImportManager\Actions;

use App\Base\Controllers\BaseActionController;
use App\Modules\ImportManager\Services\BatchRepository;
use App\Modules\ImportManager\Services\ConfigProvider;
use App\Modules\ImportManager\Services\DuplicateRuleRepository;
use App\Modules\ImportManager\Services\MappingDefinition;
use App\Modules\ImportManager\Services\MappingRepository;

class SaveMapping extends BaseActionController
{
	private ConfigProvider $config;
	private MappingRepository $repository;
	private BatchRepository $batches;
	private DuplicateRuleRepository $duplicateRules;

	public function __construct()
	{
		parent::__construct();
		$this->config = new ConfigProvider();
		$this->repository = new MappingRepository();
		$this->batches = new BatchRepository();
		$this->duplicateRules = new DuplicateRuleRepository();
	}

	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->get('target_module');
		$privileges = \App\Modules\Users\Models\Privileges::getCurrentUserPrivilegesModel();
		if (!$moduleName || !$privileges || !$privileges->hasModulePermission($moduleName)) {
			throw new \App\Exceptions\NoPermitted('LBL_PERMISSION_DENIED');
		}
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$response = new \App\Http\Vtiger_Response();

		try {
			$moduleName = $request->get('target_module');
			$batchId = (int) $request->get('batch_id');

			if ($batchId <= 0) {
				throw new \RuntimeException('Brak poprawnego identyfikatora wsadu.');
			}

			$batch = $this->fetchBatch($batchId);
			$this->guardBatchOwnership($batch);

			if ($batch['module'] !== $moduleName) {
				throw new \RuntimeException('Wybrany moduł nie zgadza się z modułem wsadu.');
			}

			$moduleModel = \App\Modules\Base\Models\Module::getInstance($moduleName);
			if (!$moduleModel) {
				throw new \RuntimeException('Nie udało się pobrać informacji o module.');
			}

			$payload = [
				'batchId' => $batchId,
				'mapping' => $this->decodeJson($request->get('mapping'), 'mapping'),
				'defaultValues' => $this->decodeJson($request->get('default_values'), 'default_values'),
				'duplicateSets' => $this->decodeJson($request->get('duplicate_sets'), 'duplicate_sets'),
				'sourceHeaders' => $this->decodeJson($request->get('source_headers'), 'source_headers'),
				'duplicateStrategy' => $request->get('duplicate_strategy') ?? $batch['duplicate_strategy'],
			];

			$definition = MappingDefinition::fromPayload($payload, $moduleModel, $this->config);

			$saveResult = $this->repository->save($definition);
			$this->duplicateRules->save($moduleName, $definition->getDuplicateSets()['required'] ?? []);

			$this->batches->update($batchId, [
				'status' => 'mapped',
				'duplicate_strategy' => $definition->getDuplicateStrategy(),
			]);

			$response->setResult([
				'mapping' => $saveResult,
				'duplicate_strategy' => $definition->getDuplicateStrategy(),
			]);
		} catch (\Throwable $exception) {
			\App\Log\Log::error('ImportManager SaveMapping failed: ' . $exception->getMessage(), 'ImportManager');
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
			throw new \RuntimeException('Nie można zmienić mapowania podczas aktywnego importu.');
		}
	}
}

