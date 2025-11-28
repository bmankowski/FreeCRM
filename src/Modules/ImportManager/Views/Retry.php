<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * Displays grid with failed staging rows and allows inline corrections.
 */

declare(strict_types=1);

namespace App\Modules\ImportManager\Views;

use App\Modules\Base\Views\Index;
use App\Modules\ImportManager\Services\BatchRepository;
use App\Modules\ImportManager\Services\ConfigProvider;
use App\Modules\ImportManager\Services\MappingDefinition;
use App\Modules\ImportManager\Services\MappingRepository;
use App\Modules\ImportManager\Services\RetryManager;

class Retry extends Index
{
	private BatchRepository $batches;
	private MappingRepository $mappings;
	private ConfigProvider $config;
	private RetryManager $retryManager;

	public function __construct()
	{
		parent::__construct();
		$this->batches = new BatchRepository();
		$this->mappings = new MappingRepository();
		$this->config = new ConfigProvider();
		$this->retryManager = new RetryManager();
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$batchId = (int) $request->get('batch_id');
		if ($batchId <= 0) {
			throw new \App\Exceptions\NoPermitted('LBL_PERMISSION_DENIED');
		}

		$batch = $this->batches->find($batchId);
		$currentUserId = \App\Modules\Users\Models\Record::getCurrentUserId();
		if (!$batch || (int) $batch['created_by'] !== (int) $currentUserId) {
			throw new \App\Exceptions\NoPermitted('LBL_PERMISSION_DENIED');
		}

		$module = \App\Modules\Base\Models\Module::getInstance($batch['module']);
		if (!$module) {
			throw new \App\Exceptions\AppException('LBL_MODULE_NOT_FOUND');
		}

		$mappingRow = $this->mappings->findByBatch($batchId);
		if (!$mappingRow) {
			throw new \App\Exceptions\AppException('LBL_HANDLER_NOT_FOUND');
		}

		$definition = MappingDefinition::fromDatabaseRow(
			$mappingRow,
			$module,
			$this->config,
			$batch['duplicate_strategy'] ?? null
		);

		$failedData = $this->retryManager->getFailedRows($batchId, 50, 0);
		$errorFields = [];
		$moduleName = $module->getName();
		$failedRows = array_map(static function ($row) use (&$errorFields, $moduleName) {
			$formattedErrors = [];
			$rowFieldErrors = [];
			$translate = static function (?string $text) use ($moduleName): string {
				if ($text === null || $text === '') {
					return '';
				}
				$translated = \App\Language::translate($text, 'ImportManager');
				if ($translated !== $text) {
					return $translated;
				}
				$translated = \App\Language::translate($text, $moduleName);
				return $translated !== $text ? $translated : $text;
			};
			if (!empty($row['errors']) && is_array($row['errors'])) {
				foreach ($row['errors'] as $error) {
					if (is_array($error)) {
						if (!empty($error['field'])) {
							$fieldNames = explode(',', (string) $error['field']);
							foreach ($fieldNames as $fieldName) {
								$fieldName = trim($fieldName);
								if ($fieldName === '') {
									continue;
								}
								$errorFields[$fieldName] = true;
								$rowFieldErrors[$fieldName] = true;
							}
						}
						$label = isset($error['label']) ? trim($translate((string) $error['label'])) : '';
						$message = isset($error['message']) ? trim($translate((string) $error['message'])) : '';
						$parts = array_filter([$label, $message]);
						if ($parts) {
							$formattedErrors[] = implode(': ', $parts);
						}
					} elseif ($error !== null && $error !== '') {
						$formattedErrors[] = (string) $error;
					}
				}
			}
			$row['errorsFormatted'] = $formattedErrors;
			$row['errorFields'] = $rowFieldErrors;
			return $row;
		}, $failedData['rows']);

		$fields = array_map(function ($row) use ($module) {
			$fieldModel = $module->getFieldByName($row['field']);
			return [
				'field' => $row['field'],
				'label' => $row['label'],
				'mandatory' => $fieldModel ? $fieldModel->isMandatory() : false,
			];
		}, $definition->getMapping());

		$viewer = $this->getViewer($request);
		$viewer->assign('BATCH', $batch);
		$viewer->assign('BATCH_ID', $batchId);
		$viewer->assign('MODULE_NAME', $module->getName());
		$viewer->assign('MAPPING_FIELDS', $fields);
		$viewer->assign('FAILED_ROWS', $failedRows);
		$viewer->assign('FAILED_TOTAL', $failedData['total']);
		$viewer->assign('ERROR_FIELDS', $errorFields);
		$viewer->view('RetryGrid.tpl', $request->getModule());
	}

	public function getFooterScripts(\App\Http\Vtiger_Request $request)
	{
		$scripts = parent::getFooterScripts($request);
		$jsFileNames = [
			'layouts.basic.modules.ImportManager.resources.retry',
		];
		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		return array_merge($scripts, $jsScriptInstances);
	}
}

