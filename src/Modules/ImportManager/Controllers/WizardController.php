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
use App\Modules\ImportManager\Services\ModuleDiscovery;
use App\Modules\ImportManager\Services\PreviewService;
use App\Modules\ImportManager\Services\UploadService;

class WizardController
{
	private ConfigProvider $config;
	private ModuleDiscovery $moduleDiscovery;
	private UploadService $uploadService;
	private PreviewService $previewService;
	private BatchRepository $batchRepository;

	public function __construct(
		?ConfigProvider $config = null,
		?ModuleDiscovery $moduleDiscovery = null,
		?UploadService $uploadService = null,
		?PreviewService $previewService = null,
		?BatchRepository $batchRepository = null
	) {
		$this->config = $config ?? new ConfigProvider();
		$this->moduleDiscovery = $moduleDiscovery ?? new ModuleDiscovery();
		$this->uploadService = $uploadService ?? new UploadService($this->config);
		$this->previewService = $previewService ?? new PreviewService($this->config);
		$this->batchRepository = $batchRepository ?? new BatchRepository();
	}

	public function buildStepOneContext(\App\Http\Vtiger_Request $request): array
	{
		$user = $request->getUser();
		$userId = $user ? (int) $user->getId() : \App\Modules\Users\Models\Record::getCurrentUserId();

		// Get sourceModule from request (can be passed as sourceModule or source_module)
		$sourceModule = $request->get('sourceModule') ?: $request->get('source_module');

		return [
			'modules' => $this->moduleDiscovery->getAvailableModules(),
			'config' => [
				'maxUploadSizeMb' => $this->config->get('fileLimits.maxUploadSizeMb', 10),
				'previewRows' => $this->config->getPreviewRows(),
				'chunkSize' => $this->config->getChunkSize(),
			],
			'recentBatches' => $this->fetchRecentBatches($userId),
			'selectedModule' => $sourceModule ?: null,
		];
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

	private function fetchRecentBatches(int $userId): array
	{
		$data = (new \App\Db\Query())
			->select(['id', 'module', 'status', 'created_at', 'total_rows', 'processed_rows'])
			->from('#__import_batches')
			->where(['created_by' => $userId])
			->orderBy(['created_at' => SORT_DESC])
			->limit(5)
			->all();

		return array_map(static function ($row) {
			return [
				'id' => (int) $row['id'],
				'module' => $row['module'],
				'status' => $row['status'],
				'created_at' => $row['created_at'],
				'progress' => (int) $row['processed_rows'] . '/' . (int) $row['total_rows'],
			];
		}, $data);
	}
}

