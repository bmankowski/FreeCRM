<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * High-level coordinator for the ImportManager wizard.
 */

declare(strict_types=1);

namespace App\Modules\ImportManager\Controllers;

use App\Modules\ImportManager\Services\BatchRepository;
use App\Modules\ImportManager\Services\ConfigProvider;
use App\Modules\ImportManager\Services\MappingDefinition;
use App\Modules\ImportManager\Services\MappingRepository;
use App\Modules\ImportManager\Services\ModuleDiscovery;
use App\Modules\ImportManager\Services\PreviewService;
use App\Modules\ImportManager\Services\UploadService;

class WizardController
{
	public const STEP_UPLOAD = 'upload';
	public const STEP_MAPPING = 'mapping';
	public const STEP_DUPLICATES = 'duplicates';
	public const STEP_STAGING = 'staging';
	public const STEP_FIX = 'fix';
	public const STEP_FINALIZE = 'finalize';

	private ConfigProvider $config;
	private ModuleDiscovery $moduleDiscovery;
	private UploadService $uploadService;
	private PreviewService $previewService;
	private BatchRepository $batchRepository;
	private MappingRepository $mappingRepository;

	public function __construct(
		?ConfigProvider $config = null,
		?ModuleDiscovery $moduleDiscovery = null,
		?UploadService $uploadService = null,
		?PreviewService $previewService = null,
		?BatchRepository $batchRepository = null,
		?MappingRepository $mappingRepository = null
	) {
		$this->config = $config ?? new ConfigProvider();
		$this->moduleDiscovery = $moduleDiscovery ?? new ModuleDiscovery();
		$this->uploadService = $uploadService ?? new UploadService($this->config);
		$this->previewService = $previewService ?? new PreviewService($this->config);
		$this->batchRepository = $batchRepository ?? new BatchRepository();
		$this->mappingRepository = $mappingRepository ?? new MappingRepository();
	}

	public function buildStepOneContext(\App\Http\Vtiger_Request $request): array
	{
		return $this->prepareUploadContext($request);
	}

	public function buildUploadContext(\App\Http\Vtiger_Request $request): array
	{
		return $this->prepareUploadContext($request);
	}

	public function buildMappingContext(int $batchId): array
	{
		$batch = $this->ensureBatchAccess($batchId);
		$preview = $this->buildPreviewForBatch($batch);
		$mappingRow = $this->mappingRepository->findByBatch($batchId);
		$definition = $mappingRow ? $this->createDefinitionFromRow($mappingRow, $batch) : null;
		$duplicateConfig = $this->config->getDuplicateConfig($batch['module']);
		$headers = $preview['headers'] ?? [];
		$fieldMetadata = $this->getModuleFieldsForMapping($batch['module']);
		$fieldsWithPresets = $this->buildFieldPresets($fieldMetadata, $headers, $definition);

		return [
			'batch' => $this->presentBatch($batch),
			'preview' => $preview,
			'definition' => $definition,
			'duplicateConfig' => $duplicateConfig,
			'fields' => $fieldsWithPresets,
			'headers' => $headers,
			'steps' => $this->buildStepProgress($batch, self::STEP_MAPPING),
			'client' => [
				'view' => self::STEP_MAPPING,
				'batch' => $this->clientBatch($batch, $definition ? ($definition['duplicateStrategy'] ?? null) : null),
				'preview' => $preview,
				'headers' => $preview['headers'] ?? [],
				'mapping' => $definition ? ($definition['mapping'] ?? []) : [],
				'defaultValues' => $definition ? ($definition['defaultValues'] ?? []) : [],
				'duplicateSets' => $definition ? ($definition['duplicateSets'] ?? ['required' => [], 'optional' => []]) : ['required' => [], 'optional' => []],
				'duplicateConfig' => [
					'activeSets' => $definition ? ($definition['duplicateSets']['required'] ?? []) : [],
					'suggestedSets' => isset($duplicateConfig['suggestedSets']) ? $duplicateConfig['suggestedSets'] : [],
				],
			],
		];
	}

	public function buildDuplicatesContext(int $batchId): array
	{
		$batch = $this->ensureBatchAccess($batchId);
		$mappingRow = $this->mappingRepository->findByBatch($batchId);
		if (!$mappingRow) {
			throw new \RuntimeException('Najpierw zapisz mapowanie pól dla tego wsadu.');
		}
		$definition = $this->createDefinitionFromRow($mappingRow, $batch);
		$duplicateConfig = $this->config->getDuplicateConfig($batch['module']);
		$fieldMetadata = $this->getModuleFieldsForMapping($batch['module']);
		$duplicateView = $this->buildDuplicateViewModel(
			$fieldMetadata,
			$definition['duplicateSets'] ?? ['required' => [], 'optional' => []],
			$duplicateConfig
		);

		return [
			'batch' => $this->presentBatch($batch),
			'definition' => $definition,
			'duplicateConfig' => $duplicateConfig,
			'fields' => $fieldMetadata,
			'duplicateView' => $duplicateView,
			'steps' => $this->buildStepProgress($batch, self::STEP_DUPLICATES),
			'client' => [
				'view' => self::STEP_DUPLICATES,
				'batch' => $this->clientBatch($batch, $definition['duplicateStrategy'] ?? null),
				'duplicateSets' => $definition['duplicateSets'] ?? ['required' => [], 'optional' => []],
				'duplicateConfig' => [
					'activeSets' => isset($duplicateConfig['activeSets']) ? $duplicateConfig['activeSets'] : [],
					'suggestedSets' => isset($duplicateConfig['suggestedSets']) ? $duplicateConfig['suggestedSets'] : [],
				],
			],
		];
	}

	public function buildStagingContext(int $batchId): array
	{
		$batch = $this->ensureBatchAccess($batchId);
		$stats = $this->buildStageStats($batch);
		
		// Pobierz informacje o zestawach duplikacji z mapowania
		$duplicateSets = [];
		$duplicateSetsFormatted = [];
		$mappingRow = $this->mappingRepository->findByBatch($batchId);
		if ($mappingRow && !empty($mappingRow['duplicate_sets'])) {
			$duplicateSetsRaw = $mappingRow['duplicate_sets'];
			\App\Log\Log::info('buildStagingContext - raw duplicate_sets from DB: ' . $duplicateSetsRaw, 'ImportManager');
			$duplicateSets = \App\Utils\Json::decode($duplicateSetsRaw) ?? [];
			\App\Log\Log::info('buildStagingContext - decoded duplicate_sets: ' . json_encode($duplicateSets), 'ImportManager');
			
			if (is_array($duplicateSets)) {
				$requiredSets = $duplicateSets['required'] ?? [];
				\App\Log\Log::info('buildStagingContext - required sets count: ' . count($requiredSets), 'ImportManager');
				
				if (!empty($requiredSets)) {
					$module = \App\Modules\Base\Models\Module::getInstance($batch['module']);
					if ($module) {
						$fieldLookup = [];
						foreach ($module->getFields() as $field) {
							$fieldLookup[$field->getName()] = $field->get('label');
						}
						$duplicateSetsFormatted = $this->formatDuplicateSets($requiredSets, $fieldLookup);
						\App\Log\Log::info('buildStagingContext - formatted sets count: ' . count($duplicateSetsFormatted), 'ImportManager');
					}
				}
			}
		} else {
			\App\Log\Log::info('buildStagingContext - no mapping row or empty duplicate_sets', 'ImportManager');
		}

		return [
			'batch' => $this->presentBatch($batch),
			'stats' => $stats,
			'duplicateSets' => $duplicateSetsFormatted,
			'steps' => $this->buildStepProgress($batch, self::STEP_STAGING),
			'client' => [
				'view' => self::STEP_STAGING,
				'batch' => $this->clientBatch($batch),
				'stats' => $stats,
				'readyRows' => $stats['ready'],
				'errorRows' => $stats['errors'],
				'duplicateStrategy' => $batch['duplicate_strategy'] ?? 'skip',
			],
		];
	}

	public function buildFinalizeContext(int $batchId): array
	{
		$batch = $this->ensureBatchAccess($batchId);
		$stats = $this->buildStageStats($batch);
		
		// Przygotuj gotowe stringi z tłumaczeniami (bez sprintf w szablonie)
		$readyInfoText = \App\Language::translate('LBL_IMPORT_READY_INFO', 'ImportManager', $stats['ready'], $stats['errors']);
		
		$importSummary = [
			'status' => $batch['status'],
			'processed' => (int) ($batch['processed_rows'] ?? 0),
			'errors' => (int) ($batch['error_rows'] ?? 0),
		];
		
		$resultMessageText = null;
		if ($importSummary['processed'] > 0 || $importSummary['errors'] > 0) {
			$resultMessageText = \App\Language::translate('LBL_IMPORT_RESULT_MESSAGE_SHORT', 'ImportManager', $importSummary['processed'], $importSummary['errors']);
		}

		return [
			'batch' => $this->presentBatch($batch),
			'stats' => $stats,
			'importSummary' => $importSummary,
			'readyInfoText' => $readyInfoText,
			'resultMessageText' => $resultMessageText,
			'steps' => $this->buildStepProgress($batch, self::STEP_FINALIZE),
			'client' => [
				'view' => self::STEP_FINALIZE,
				'batch' => $this->clientBatch($batch),
				'stats' => $stats,
				'readyRows' => $stats['ready'],
				'import' => $importSummary,
			],
		];
	}

	public function buildStepProgress(?array $batch, ?string $activeStep = null): array
	{
		$steps = $this->stepConfig();
		$currentStep = $activeStep ?? ($batch ? $this->resolveCurrentStep($batch) : self::STEP_UPLOAD);
		$currentIndex = $this->stepIndex($currentStep);
		$hasBatch = $batch !== null;
		$errorRows = $batch ? (int) ($batch['error_rows'] ?? 0) : 0;

		foreach ($steps as $key => &$step) {
			$index = $this->stepIndex($key);
			$step['key'] = $key;
			$step['label'] = \App\Language::translate($step['labelKey'], 'ImportManager');
			$step['active'] = $key === $currentStep;
			$step['completed'] = $hasBatch && $index < $currentIndex;
			$step['enabled'] = $this->isStepEnabled($key, $batch, $errorRows);
			$step['url'] = $step['enabled'] ? $this->buildStepUrl($key, $batch) : null;
		}

		if ($errorRows === 0) {
			$steps[self::STEP_FIX]['enabled'] = false;
			$steps[self::STEP_FIX]['url'] = null;
			$steps[self::STEP_FIX]['active'] = false;
		}

		return array_values($steps);
	}

	public function handleUpload(\App\Http\Vtiger_Request $request): array
	{
		if (empty($_FILES['import_file'])) {
			throw new \RuntimeException('Nie przesłano pliku do importu.');
		}

		$user = $request->getUser();
		$userId = $user ? (int) $user->getId() : \App\Modules\Users\Models\Record::getCurrentUserId();

		$payload = [
			'target_module' => $request->get('target_module'),
			'delimiter' => $request->get('delimiter'),
			'enclosure' => $request->get('enclosure'),
			'encoding' => $request->get('encoding'),
			'format' => $request->get('format'),
			'xpath' => $request->get('xpath'),
			'duplicate_strategy' => $request->get('duplicate_strategy'),
		];

		$uploadResult = $this->uploadService->handle($_FILES['import_file'], $payload, $userId);

		$parserOptions = [
			'delimiter' => $payload['delimiter'],
			'enclosure' => $payload['enclosure'],
			'encoding' => $payload['encoding'],
			'xpath' => $payload['xpath'],
		];

		$previewRows = $request->get('preview_rows');
		if ($previewRows !== null && $previewRows !== '') {
			$previewRows = (int) $previewRows;
			if ($previewRows > 0) {
				$parserOptions['preview_rows'] = $previewRows;
			}
		}

		$preview = $this->previewService->build(
			$uploadResult['format'],
			$uploadResult['absolutePath'],
			$parserOptions
		);

		$this->batchRepository->attachPreviewStats($uploadResult['batchId'], count($preview['rows']));

		return [
			'batchId' => $uploadResult['batchId'],
			'file' => $uploadResult['file'],
			'preview' => $preview,
		];
	}

	private function prepareUploadContext(\App\Http\Vtiger_Request $request): array
	{
		$user = $request->getUser();
		$userId = $user ? (int) $user->getId() : \App\Modules\Users\Models\Record::getCurrentUserId();
		$sourceModule = $request->get('sourceModule') ?: $request->get('source_module');
		$recent = $this->fetchRecentBatches($userId);

		return [
			'modules' => $this->moduleDiscovery->getAvailableModules(),
			'config' => [
				'maxUploadSizeMb' => $this->config->get('fileLimits.maxUploadSizeMb', 10),
				'previewRows' => $this->config->getPreviewRows(),
				'chunkSize' => $this->config->getChunkSize(),
			],
			'recentBatches' => $recent,
			'selectedModule' => $sourceModule ?: null,
			'steps' => $this->buildStepProgress(null, self::STEP_UPLOAD),
			'client' => [
				'view' => self::STEP_UPLOAD,
				'recentBatches' => $recent,
			],
		];
	}

	private function fetchRecentBatches(int $userId): array
	{
		$data = (new \App\Db\Query())
			->select(['id', 'module', 'status', 'created_at', 'total_rows', 'processed_rows', 'error_rows'])
			->from('#__import_batches')
			->where(['created_by' => $userId])
			->orderBy(['created_at' => SORT_DESC])
			->limit(5)
			->all();

		return array_map(function ($row) {
			$row = $this->decorateBatchRow($row);
			$row['progress'] = (int) ($row['processed_rows'] ?? 0) . '/' . (int) ($row['total_rows'] ?? 0);
			unset($row['processed_rows'], $row['total_rows'], $row['error_rows']);
			return $row;
		}, $data);
	}

	private function decorateBatchRow(array $row): array
	{
		$step = $this->resolveCurrentStep($row);

		return [
			'id' => (int) $row['id'],
			'module' => $row['module'],
			'status' => $row['status'],
			'created_at' => $row['created_at'],
			'processed_rows' => $row['processed_rows'] ?? 0,
			'total_rows' => $row['total_rows'] ?? 0,
			'error_rows' => $row['error_rows'] ?? 0,
			'step' => $step,
			'continue_url' => $this->buildStepUrl($step, $row),
		];
	}

	private function ensureBatchAccess(int $batchId): array
	{
		$batch = $this->batchRepository->find($batchId);
		if (!$batch) {
			throw new \RuntimeException('Nie znaleziono wskazanego wsadu importu.');
		}
		$currentUserId = \App\Modules\Users\Models\Record::getCurrentUserId();
		if ((int) $batch['created_by'] !== (int) $currentUserId) {
			throw new \App\Exceptions\NoPermitted('LBL_PERMISSION_DENIED');
		}
		return $batch;
	}

	private function presentBatch(array $batch): array
	{
		return [
			'id' => (int) $batch['id'],
			'module' => $batch['module'],
			'status' => $batch['status'],
			'file_name' => $batch['file_name'] ?? null,
			'file_size' => isset($batch['file_size']) ? (int) $batch['file_size'] : null,
			'duplicate_strategy' => $batch['duplicate_strategy'] ?? 'skip',
			'total_rows' => isset($batch['total_rows']) ? (int) $batch['total_rows'] : 0,
			'processed_rows' => isset($batch['processed_rows']) ? (int) $batch['processed_rows'] : 0,
			'error_rows' => isset($batch['error_rows']) ? (int) $batch['error_rows'] : 0,
			'created_at' => $batch['created_at'] ?? null,
			'updated_at' => $batch['updated_at'] ?? null,
		];
	}

	private function buildPreviewForBatch(array $batch): array
	{
		$path = $batch['file_path'] ?? '';
		if ($path === '') {
			throw new \RuntimeException('Brakuje ścieżki do pliku importu.');
		}
		$absolutePath = ROOT_DIRECTORY . '/' . ltrim((string) $path, '/');
		if (!is_file($absolutePath)) {
			throw new \RuntimeException('Plik importu nie istnieje. Prześlij go ponownie.');
		}

		return $this->previewService->build(
			(string) $batch['format'],
			$absolutePath,
			[
				'delimiter' => $batch['delimiter'] ?? null,
				'enclosure' => $batch['enclosure'] ?? null,
				'encoding' => $batch['encoding'] ?? null,
				'xpath' => $batch['xpath'] ?? null,
			]
		);
	}

	private function createDefinitionFromRow(array $row, array $batch): array
	{
		$module = \App\Modules\Base\Models\Module::getInstance($batch['module']);
		if (!$module) {
			throw new \RuntimeException('Nie można zainicjować modułu docelowego.');
		}
		$definition = MappingDefinition::fromDatabaseRow(
			$row,
			$module,
			$this->config,
			$batch['duplicate_strategy'] ?? null
		);

		return $this->normalizeDefinition($definition);
	}

	private function normalizeDefinition(MappingDefinition $definition): array
	{
		return [
			'batchId' => $definition->getBatchId(),
			'mapping' => $definition->getMapping(),
			'defaultValues' => $definition->getDefaultValues(),
			'duplicateSets' => $definition->getDuplicateSets(),
			'options' => $definition->getOptions(),
			'duplicateStrategy' => $definition->getDuplicateStrategy(),
		];
	}

	private function clientBatch(array $batch, ?string $strategyOverride = null): array
	{
		return [
			'id' => (int) $batch['id'],
			'module' => $batch['module'],
			'status' => $batch['status'],
			'file_name' => $batch['file_name'] ?? null,
			'file_size' => isset($batch['file_size']) ? (int) $batch['file_size'] : null,
			'duplicate_strategy' => $strategyOverride ?? ($batch['duplicate_strategy'] ?? 'skip'),
		];
	}

	private function buildStageStats(array $batch): array
	{
		$total = (int) ($batch['total_rows'] ?? 0);
		$errors = (int) ($batch['error_rows'] ?? 0);
		$processed = (int) ($batch['processed_rows'] ?? 0);
		$ready = max($total - $errors, 0);

		return [
			'total' => $total,
			'processed' => $processed,
			'errors' => $errors,
			'ready' => $ready,
			'status' => $batch['status'],
			'updated_at' => $batch['updated_at'] ?? null,
		];
	}

	private function resolveCurrentStep(array $batch): string
	{
		$status = $batch['status'] ?? '';
		$errors = (int) ($batch['error_rows'] ?? 0);
		return match ($status) {
			'uploaded' => self::STEP_MAPPING,
			'mapped' => self::STEP_DUPLICATES,
			'duplicates_ready' => self::STEP_STAGING,
			'staged' => $errors > 0 ? self::STEP_FIX : self::STEP_FINALIZE,
			'running', 'completed', 'failed' => self::STEP_FINALIZE,
			default => self::STEP_UPLOAD,
		};
	}

	private function stepConfig(): array
	{
		return [
			self::STEP_UPLOAD => ['labelKey' => 'LBL_STEP_UPLOAD', 'view' => 'Upload'],
			self::STEP_MAPPING => ['labelKey' => 'LBL_STEP_MAPPING', 'view' => 'Mapping'],
			self::STEP_DUPLICATES => ['labelKey' => 'LBL_STEP_DUPLICATES', 'view' => 'Duplicates'],
			self::STEP_STAGING => ['labelKey' => 'LBL_STEP_STAGING', 'view' => 'Staging'],
			self::STEP_FIX => ['labelKey' => 'LBL_STEP_FIX_ERRORS', 'view' => 'Retry'],
			self::STEP_FINALIZE => ['labelKey' => 'LBL_STEP_FINALIZE', 'view' => 'Finalize'],
		];
	}

	private function stepIndex(string $step): int
	{
		static $order = [
			self::STEP_UPLOAD,
			self::STEP_MAPPING,
			self::STEP_DUPLICATES,
			self::STEP_STAGING,
			self::STEP_FIX,
			self::STEP_FINALIZE,
		];
		$index = array_search($step, $order, true);
		return $index === false ? 0 : (int) $index;
	}

	private function isStepEnabled(string $key, ?array $batch, int $errorRows): bool
	{
		if ($key === self::STEP_UPLOAD) {
			return true;
		}
		if (!$batch) {
			return false;
		}

		$status = $batch['status'] ?? '';
		return match ($key) {
			self::STEP_MAPPING => true,
			self::STEP_DUPLICATES => in_array($status, ['mapped', 'duplicates_ready', 'staged', 'running', 'completed', 'failed'], true),
			self::STEP_STAGING => in_array($status, ['duplicates_ready', 'staged', 'running', 'completed', 'failed'], true),
			self::STEP_FIX => $errorRows > 0 && $status === 'staged',
			self::STEP_FINALIZE => in_array($status, ['staged', 'running', 'completed', 'failed'], true) && ($errorRows === 0 || $status === 'completed'),
			default => false,
		};
	}

	private function buildStepUrl(string $stepKey, ?array $batch): ?string
	{
		if ($stepKey === self::STEP_UPLOAD) {
			return 'index.php?module=ImportManager&view=Upload';
		}
		if (!$batch) {
			return null;
		}
		$id = (int) $batch['id'];
		return match ($stepKey) {
			self::STEP_MAPPING => 'index.php?module=ImportManager&view=Mapping&batch_id=' . $id,
			self::STEP_DUPLICATES => 'index.php?module=ImportManager&view=Duplicates&batch_id=' . $id,
			self::STEP_STAGING => 'index.php?module=ImportManager&view=Staging&batch_id=' . $id,
			self::STEP_FIX => 'index.php?module=ImportManager&view=Retry&batch_id=' . $id,
			self::STEP_FINALIZE => 'index.php?module=ImportManager&view=Finalize&batch_id=' . $id,
			default => null,
		};
	}

	public function getStepUrlForBatch(int $batchId): ?string
	{
		$batch = $this->ensureBatchAccess($batchId);
		$step = $this->resolveCurrentStep($batch);
		return $this->buildStepUrl($step, $batch);
	}

	/**
	 * Build metadata for module fields used on mapping step.
	 */
	private function getModuleFieldsForMapping(string $moduleName): array
	{
		$moduleModel = \App\Modules\Base\Models\Module::getInstance($moduleName);
		if (!$moduleModel) {
			throw new \RuntimeException('Nie można zainicjować modułu docelowego.');
		}

		$fields = [];
		foreach ($moduleModel->getFields() as $fieldModel) {
			if (!$fieldModel->isActiveField() || !$fieldModel->isEditable()) {
				continue;
			}

			$label = \App\Language::translate($fieldModel->getFieldLabel(), $moduleName);
			$name = $fieldModel->getName();
			$fields[] = [
				'name' => $name,
				'label' => $label,
				'mandatory' => $fieldModel->isMandatory(),
				'type' => $fieldModel->getFieldDataType(),
				'name_normalized' => $this->normalizeFieldToken($name),
				'label_normalized' => $this->normalizeFieldToken($label),
			];
		}

		usort($fields, static fn($a, $b) => strcasecmp($a['label'], $b['label']));
		return $fields;
	}

	/**
	 * Attach mapping presets (source column index + default value) for each field.
	 */
	private function buildFieldPresets(array $fields, array $headers, ?array $definition): array
	{
		$savedMap = [];
		$defaultValues = [];

		if ($definition) {
			foreach (($definition['mapping'] ?? []) as $row) {
				if (!empty($row['field'])) {
					$savedMap[$row['field']] = $row;
				}
			}
			$defaultValues = $definition['defaultValues'] ?? [];
		}

		$usedHeaders = [];
		$result = [];
		foreach ($fields as $field) {
			$preset = [
				'sourceIndex' => null,
				'defaultValue' => array_key_exists($field['name'], $defaultValues) ? (string) $defaultValues[$field['name']] : '',
			];

			if (isset($savedMap[$field['name']])) {
				$preset['sourceIndex'] = $this->resolveSavedSourceIndex($savedMap[$field['name']], $headers);
			}

			if ($preset['sourceIndex'] === null) {
				$preset['sourceIndex'] = $this->guessHeaderIndex($field, $headers, $usedHeaders);
			}

			$result[] = [
				'name' => $field['name'],
				'label' => $field['label'],
				'mandatory' => $field['mandatory'],
				'type' => $field['type'],
				'preset' => $preset,
			];
		}

		return $result;
	}

	private function resolveSavedSourceIndex(array $savedRow, array $headers): ?int
	{
		if (isset($savedRow['index']) && $savedRow['index'] !== null && $savedRow['index'] !== '') {
			return (int) $savedRow['index'];
		}

		if (!empty($savedRow['column'])) {
			foreach ($headers as $index => $header) {
				if ($header === $savedRow['column']) {
					return (int) $index;
				}
			}
		}

		return null;
	}

	private function guessHeaderIndex(array $field, array $headers, array &$usedHeaders): ?int
	{
		$candidates = [];
		if (!empty($field['name_normalized'])) {
			$candidates[] = $field['name_normalized'];
		}
		if (!empty($field['label_normalized'])) {
			$candidates[] = $field['label_normalized'];
		}

		if (!$candidates) {
			return null;
		}

		foreach ($headers as $index => $header) {
			if (!empty($usedHeaders[$index])) {
				continue;
			}
			$normalizedHeader = $this->normalizeFieldToken((string) $header);
			if ($normalizedHeader === '') {
				continue;
			}
			if (in_array($normalizedHeader, $candidates, true)) {
				$usedHeaders[$index] = true;
				return (int) $index;
			}
		}

		return null;
	}

	private function normalizeFieldToken(?string $value): string
	{
		if ($value === null || $value === '') {
			return '';
		}
		$lower = mb_strtolower($value, 'UTF-8');
		return preg_replace('/\W+/u', '', $lower) ?? '';
	}

	private function buildDuplicateViewModel(array $fields, array $duplicateSets, array $config): array
	{
		$fieldLookup = $this->buildFieldLookup($fields);
		$requiredSets = $this->formatDuplicateSets($duplicateSets['required'] ?? [], $fieldLookup);
		$activeKeys = array_map(static fn($set) => $set['key'], $requiredSets);

		$suggestedSets = [];
		foreach (($config['suggestedSets'] ?? []) as $set) {
			$normalized = $this->sanitizeDuplicateSetForView($set, $fieldLookup);
			if (!$normalized) {
				continue;
			}
			$key = $this->serializeDuplicateSet($normalized);
			$suggestedSets[$key] = [
				'key' => $key,
				'fields' => $normalized,
				'label' => $this->describeDuplicateSetForView($normalized, $fieldLookup),
				'active' => in_array($key, $activeKeys, true),
			];
		}

		return [
			'required' => array_map(static fn($set) => [
				'key' => $set['key'],
				'fields' => $set['fields'],
				'label' => $set['label'],
			], $requiredSets),
			'suggested' => array_values($suggestedSets),
		];
	}

	private function buildFieldLookup(array $fields): array
	{
		$lookup = [];
		foreach ($fields as $field) {
			if (empty($field['name'])) {
				continue;
			}
			$lookup[strtolower($field['name'])] = $field;
		}
		return $lookup;
	}

	private function formatDuplicateSets(array $sets, array $fieldLookup): array
	{
		$result = [];
		foreach ($sets as $set) {
			$normalized = $this->sanitizeDuplicateSetForView($set, $fieldLookup);
			if (!$normalized) {
				continue;
			}
			$key = $this->serializeDuplicateSet($normalized);
			$result[] = [
				'key' => $key,
				'fields' => $normalized,
				'label' => $this->describeDuplicateSetForView($normalized, $fieldLookup),
			];
		}
		return $result;
	}

	private function sanitizeDuplicateSetForView($set, array $fieldLookup): array
	{
		if (!is_array($set)) {
			return [];
		}
		$result = [];
		foreach ($set as $fieldName) {
			$key = strtolower(trim((string) $fieldName));
			if ($key === '' || !isset($fieldLookup[$key])) {
				return [];
			}
			$name = $fieldLookup[$key]['name'] ?? $fieldName;
			if (!in_array($name, $result, true)) {
				$result[] = $name;
			}
		}
		return $result;
	}

	private function describeDuplicateSetForView(array $set, array $fieldLookup): string
	{
		$labels = [];
		foreach ($set as $fieldName) {
			$key = strtolower($fieldName);
			$labels[] = $fieldLookup[$key]['label'] ?? $fieldName;
		}
		return implode(' + ', $labels);
	}

	private function serializeDuplicateSet(array $set): string
	{
		$normalized = array_map(static fn($value) => strtolower((string) $value), $set);
		sort($normalized);
		return implode('::', $normalized);
	}
}

