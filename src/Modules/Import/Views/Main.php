<?php

namespace App\Modules\Import\Views;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * *********************************************************************************** */


use App\Http\Vtiger_Request;
class Main extends \App\Base\Controllers\BaseViewController
{

	public $request;
	public $user;
	public $numberOfRecords;

	public function process(\App\Http\Vtiger_Request $request)
	{
		return;
	}

	public function __construct($request, $user)
	{
		$this->request = $request;
		$this->user = $user;
	}

	public static function import($request, $user)
	{
		$importController = new \App\Modules\Import\Views\Main($request, $user);

		$importController->saveMap();
		$fileReadStatus = $importController->copyFromFileToDB();
		if ($fileReadStatus) {
			$importController->queueDataImport();
		}

		$isImportScheduled = $importController->request->get('is_scheduled');

		if ($isImportScheduled) {
			$importInfo = \App\Modules\Import\Actions\Queue::getUserCurrentImportInfo($importController->user);
			self::showScheduledStatus($importInfo);
		} else {
			$importController->triggerImport();
		}
	}

	public function triggerImport($batchImport = false)
	{
		$importInfo = \App\Modules\Import\Actions\Queue::getImportInfo($this->request->get('module'), $this->user);
		$importDataController = new \App\Modules\Import\Actions\Data($importInfo, $this->user);

		if (!$batchImport) {
			if (!$importDataController->initializeImport()) {
				\App\Modules\Import\Helpers\Utils::showErrorPage(\App\Runtime\Vtiger_Language_Handler::translate('ERR_FAILED_TO_LOCK_MODULE', 'Import'));
				throw new \App\Exceptions\AppException(\App\Runtime\Vtiger_Language_Handler::translate('ERR_FAILED_TO_LOCK_MODULE', 'Import'));
			}
		}

		$importDataController->importData();
		\App\Modules\Import\Actions\Queue::updateStatus($importInfo['id'], \App\Modules\Import\Actions\Queue::$IMPORT_STATUS_HALTED);
		$importInfo = \App\Modules\Import\Actions\Queue::getImportInfo($this->request->get('module'), $this->user);

		self::showImportStatus($importInfo, $this->user);
	}

	/**
	 * Show import status
	 * @param array $importInfo
	 * @param \App\Modules\Users\Models\Record $user
	 * @throws \App\Exceptions\AppException
	 */
	public static function showImportStatus($importInfo, $user)
	{
		if (empty($importInfo)) {
			\App\Modules\Import\Helpers\Utils::showErrorPage(\App\Runtime\Vtiger_Language_Handler::translate('ERR_IMPORT_INTERRUPTED', 'Import'));
			throw new \App\Exceptions\AppException(\App\Runtime\Vtiger_Language_Handler::translate('ERR_IMPORT_INTERRUPTED', 'Import'));
		}
		$importDataController = new \App\Modules\Import\Actions\Data($importInfo, $user);
		if ($importInfo['temp_status'] === \App\Modules\Import\Actions\Queue::$IMPORT_STATUS_HALTED ||
			$importInfo['temp_status'] === \App\Modules\Import\Actions\Queue::$IMPORT_STATUS_NONE) {
			$continueImport = true;
		} else {
			$continueImport = false;
		}

		$importStatusCount = $importDataController->getImportStatusCount();
		$totalRecords = $importStatusCount['TOTAL'];
		if ($totalRecords > ($importStatusCount['IMPORTED'] + $importStatusCount['FAILED'])) {
			self::showCurrentStatus($importInfo, $importStatusCount, $continueImport);
		} else {
			$importDataController->finishImport();
			self::showResult($importInfo, $importStatusCount);
		}
	}

	public static function showCurrentStatus($importInfo, $importStatusCount, $continueImport)
	{
		$moduleName = $importInfo['module'];
		$importId = $importInfo['id'];

		$viewer = new CRM_Viewer();

		$viewer->assign('FOR_MODULE', $moduleName);
		$viewer->assign('MODULE', 'Import');
		$viewer->assign('IMPORT_ID', $importId);
		$viewer->assign('IMPORT_RESULT', $importStatusCount);
		$viewer->assign('INVENTORY_MODULES', \App\Utils\Utils::getInventoryModules());
		$viewer->assign('CONTINUE_IMPORT', $continueImport);

		$viewer->view('ImportStatus.tpl', 'Import');
	}

	public static function showResult($importInfo, $importStatusCount)
	{
		$moduleName = $importInfo['module'];
		$ownerId = $importInfo['user_id'];

		$viewer = new CRM_Viewer();
		$viewer->assign('FOR_MODULE', $moduleName);
		$viewer->assign('MODULE', 'Import');
		$viewer->assign('OWNER_ID', $ownerId);
		$viewer->assign('IMPORT_RESULT', $importStatusCount);
		$viewer->assign('INVENTORY_MODULES', \App\Utils\Utils::getInventoryModules());
		$viewer->assign('TYPE', $importInfo['type']);
		$viewer->assign('MERGE_ENABLED', $importInfo['merge_type']);

		$viewer->view('ImportResult.tpl', 'Import');
	}

	public static function showScheduledStatus($importInfo)
	{
		$moduleName = $importInfo['module'];
		$importId = $importInfo['id'];

		$viewer = new CRM_Viewer();

		$viewer->assign('FOR_MODULE', $moduleName);
		$viewer->assign('MODULE', 'Import');
		$viewer->assign('IMPORT_ID', $importId);

		$viewer->view('ImportSchedule.tpl', 'Import');
	}

	public function saveMap()
	{
		$saveMap = $this->request->get('save_map');
		$mapName = $this->request->get('save_map_as');
		if ($saveMap && !empty($mapName)) {
			$fieldMapping = $this->request->get('field_mapping');
			$fileReader = \App\Modules\Import\Models\Module::getFileReader($this->request, $this->user);
			if ($fileReader === null) {
				return false;
			}
			$hasHeader = $fileReader->hasHeader();
			if ($hasHeader) {
				$firstRowData = $fileReader->getFirstRowData($hasHeader);
				$headers = array_keys($firstRowData['LBL_STANDARD_FIELDS']);
				if (isset($firstRowData['LBL_INVENTORY_FIELDS'])) {
					$headers = array_merge($headers, array_keys($firstRowData['LBL_INVENTORY_FIELDS']));
				}
				foreach ($fieldMapping as $fieldName => $index) {
					$saveMapping["$headers[$index]"] = $fieldName;
				}
			} else {
				$saveMapping = array_flip($fieldMapping);
			}
			$map = [];
			$map['name'] = $mapName;
			$map['content'] = $saveMapping;
			$map['module'] = $this->request->get('module');
			$map['has_header'] = ($hasHeader) ? 1 : 0;
			$map['assigned_user_id'] = $this->user->id;
			(new \App\Modules\Import\Models\Map($map, $this->user))->save();
		}
	}

	public function copyFromFileToDB()
	{
		$fileReader = \App\Modules\Import\Models\Module::getFileReader($this->request, $this->user);
		$fileReader->read();
		$fileReader->deleteFile();
		if ($fileReader->getStatus() === 'success') {
			$this->numberOfRecords = $fileReader->getNumberOfRecordsRead();
			return true;
		} else {
			\App\Modules\Import\Helpers\Utils::showErrorPage(\App\Runtime\Vtiger_Language_Handler::translate('ERR_FILE_READ_FAILED', 'Import') . ' - ' .
				\App\Runtime\Vtiger_Language_Handler::translate($fileReader->getErrorMessage(), 'Import'));
			return false;
		}
	}

	public function queueDataImport()
	{
		$immediateImportRecordLimit = \App\Core\AppConfig::module('Import', 'IMMEDIATE_IMPORT_LIMIT');

		$numberOfRecordsToImport = $this->numberOfRecords;
		if ($numberOfRecordsToImport > $immediateImportRecordLimit) {
			$this->request->set('is_scheduled', true);
		}
		\App\Modules\Import\Actions\Queue::add($this->request, $this->user);
	}

	public static function deleteMap($request)
	{
		$moduleName = $request->getModule();
		$mapId = $request->get('mapid');
		if (!empty($mapId)) {
			\App\Modules\Import\Models\Map::markAsDeleted($mapId);
		}

		$viewer = new CRM_Viewer();
		$viewer->assign('FOR_MODULE', $moduleName);
		$viewer->assign('MODULE', 'Import');
		$viewer->assign('SAVED_MAPS', \App\Modules\Import\Models\Map::getAllByModule($moduleName));
		$viewer->view('Import_Saved_Maps.tpl', 'Import');
	}
}

?>
