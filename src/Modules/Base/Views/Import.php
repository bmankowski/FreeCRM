<?php

namespace App\Modules\Base\Views;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * *********************************************************************************** */


class Import  extends \App\Modules\Base\Views\Index
{
	private static $bulkSaveMode = false;

	public function __construct()
	{
		parent::__construct();
		$this->exposeMethod('continueImport');
		$this->exposeMethod('uploadAndParse');
		$this->exposeMethod('importBasicStep');
		$this->exposeMethod('import');
		$this->exposeMethod('undoImport');
		$this->exposeMethod('lastImportedRecords');
		$this->exposeMethod('deleteMap');
		$this->exposeMethod('clearCorruptedData');
		$this->exposeMethod('cancelImport');
		$this->exposeMethod('checkImportStatus');
	}

	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		$currentUserPriviligesModel = \App\Modules\Users\Models\Privileges::getCurrentUserPrivilegesModel();
		if (!$currentUserPriviligesModel->hasModuleActionPermission($request->getModule(), 'Import')) {
			throw new \App\Exceptions\NoPermitted('LBL_PERMISSION_DENIED');
		}
	}

	/**
	 * Process
	 * @param \App\Http\Vtiger_Request $request
	 */
	public function process(\App\Http\Vtiger_Request $request)
	{
		$mode = $request->getMode();
		if (!empty($mode)) {
			// Added to check the status of import
			if ($mode === 'continueImport' || $mode === 'uploadAndParse' || $mode === 'importBasicStep') {
				$this->checkImportStatus($request);
			}
			$this->invokeExposedMethod($mode, $request);
		} else {
			$this->checkImportStatus($request);
			$this->importBasicStep($request);
		}
	}

	/**
	 * Function to get the list of Script models to be included
	 * @param \App\Http\Vtiger_Request $request
	 * @return <Array> - List of \App\Modules\Base\Models\JsScript instances
	 */
	public function getFooterScripts(\App\Http\Vtiger_Request $request)
	{
		$headerScriptInstances = parent::getFooterScripts($request);

		$jsFileNames = array(
			'modules.Import.resources.Import'
		);

		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		$headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);
		return $headerScriptInstances;
	}

	/**
	 * First step to import records
	 * @param \App\Http\Vtiger_Request $request
	 */
	public function importBasicStep(\App\Http\Vtiger_Request $request)
	{
		$uploadMaxSize = \App\AppConfig::main('upload_maxsize');
		$moduleName = $request->getModule();

		$importModule = \App\Modules\Base\Models\Module::getInstance('Import')->setImportModule($moduleName);
		$viewer = $this->getViewer($request);
		$viewer->assign('FOR_MODULE', $moduleName);
		$viewer->assign('MODULE', 'Import');
		$viewer->assign('XML_IMPORT_TPL', \App\Modules\Import\Models\Module::getListTplForXmlType($moduleName));
		$viewer->assign('SUPPORTED_FILE_TYPES', \App\Modules\Import\Models\Module::getSupportedFileExtensions($moduleName));
		$viewer->assign('SUPPORTED_FILE_TYPES_TEXT', \App\Modules\Import\Models\Module::getSupportedFileExtensionsDescription($moduleName));
		$viewer->assign('SUPPORTED_FILE_ENCODING', \App\Modules\Import\Models\Module::getSupportedFileEncoding());
		$viewer->assign('SUPPORTED_DELIMITERS', \App\Modules\Import\Models\Module::getSupportedDelimiters());
		$viewer->assign('AUTO_MERGE_TYPES', \App\Modules\Import\Models\Module::getAutoMergeTypes());
		$viewer->assign('AVAILABLE_BLOCKS', $importModule->getFieldsByBlocks());
		$viewer->assign('FOR_MODULE_MODEL', $importModule->getImportModuleModel());
		$viewer->assign('ERROR_MESSAGE', $request->get('error_message'));
		$viewer->assign('IMPORT_UPLOAD_SIZE', $uploadMaxSize);
		$viewer->assign('IMPORT_UPLOAD_SIZE_MB', round($uploadMaxSize / 1024 / 1024, 2));
		return $viewer->view('ImportBasicStep.tpl', 'Import');
	}

	/**
	 * Function verifies, validates and uploads data for import
	 * @param \App\Http\Vtiger_Request $request
	 */
	public function uploadAndParse(\App\Http\Vtiger_Request $request)
	{
		if (\App\Modules\Import\Helpers\Utils::validateFileUpload($request)) {
			$moduleName = $request->getModule();
			$user = $request->getUser();
			$fileReader = \App\Modules\Import\Models\Module::getFileReader($request, $user);
			if ($fileReader === null) {
				$this->importBasicStep($request);
				return;
			}
			$hasHeader = $fileReader->hasHeader();
			$rowData = $fileReader->getFirstRowData($hasHeader);
			$viewer = $this->getViewer($request);
			$autoMerge = $request->get('auto_merge');
			if (!$autoMerge) {
				$request->set('merge_type', 0);
				$request->set('merge_fields', '');
			} else {
				$viewer->assign('MERGE_FIELDS', \App\Utils\Json::encode($request->get('merge_fields')));
			}

			$moduleName = $request->getModule();
			$moduleModel = \App\Modules\Base\Models\Module::getInstance($moduleName);
			$moduleMeta = $moduleModel->getModuleMeta();

			$viewer->assign('DATE_FORMAT', $user->date_format);
			$viewer->assign('FOR_MODULE', $moduleName);
			$viewer->assign('MODULE', 'Import');

			$viewer->assign('HAS_HEADER', $hasHeader);
			$viewer->assign('ROW_1_DATA', ($rowData && $rowData['LBL_STANDARD_FIELDS']) ? $rowData : ['LBL_STANDARD_FIELDS' => $rowData]);
			$viewer->assign('USER_INPUT', $request);

			if ($moduleModel->isInventory()) {
				$inventoryFieldModel = \App\Modules\Base\Models\InventoryField::getInstance($moduleName);
				$inventoryFields = $inventoryFieldModel->getFields(true);
				$inventoryFieldsBlock = [];
				$blocksName = ['LBL_HEADLINE', 'LBL_BASIC_VERSE', 'LBL_ADDITIONAL_VERSE'];
				foreach ($inventoryFields as $key => $data) {
					$inventoryFieldsBlock[$blocksName[$key]] = $data;
				}
				$viewer->assign('INVENTORY_BLOCKS', $inventoryFieldsBlock);
				$viewer->assign('INVENTORY', true);
			}
			$importModule = \App\Modules\Base\Models\Module::getInstance('Import')->setImportModule($moduleName);
			$viewer->assign('AVAILABLE_BLOCKS', $importModule->getFieldsByBlocks());
			$viewer->assign('ENCODED_MANDATORY_FIELDS', \App\Utils\Json::encode($moduleMeta->getMandatoryFields()));
			$viewer->assign('SAVED_MAPS', \App\Modules\Import\Models\Map::getAllByModule($moduleName));
			$viewer->assign('USERS_LIST', \App\Modules\Import\Helpers\Utils::getAssignedToUserList($moduleName));
			$viewer->assign('GROUPS_LIST', \App\Modules\Import\Helpers\Utils::getAssignedToGroupList($moduleName));
			$viewer->assign('CREATE_RECORDS_BY_MODEL', in_array($request->get('type'), ['xml', 'zip']));
			return $viewer->view('ImportAdvanced.tpl', 'Import');
		} else {
			$this->importBasicStep($request);
		}
	}

	public function import(\App\Http\Vtiger_Request $request)
	{
		$user = $request->getUser();
		\App\Modules\Import\Views\Main::import($request, $user);
	}

	/**
	 * Continue import
	 * @param \App\Http\Vtiger_Request $request
	 */
	public function continueImport(\App\Http\Vtiger_Request $request)
	{
		$this->checkImportStatus($request);
	}

	public function undoImport(\App\Http\Vtiger_Request $request)
	{
		$previousBulkSaveMode = self::$bulkSaveMode;
		$viewer = new CRM_Viewer();
		$moduleName = $request->getModule();
		$ownerId = $request->get('foruser');
		$type = $request->get('type');
		$user = $request->getUser();

		if (!$user->isAdminUser() && $user->id != $ownerId) {
			$viewer->assign('MESSAGE', 'LBL_PERMISSION_DENIED');
			$viewer->view('OperationNotPermitted.tpl', 'Vtiger');
			throw new \App\Exceptions\NoPermitted('LBL_PERMISSION_DENIED');
		}
		if (empty($type)) {
			self::$bulkSaveMode = true;
		} else {
			self::$bulkSaveMode = false;
		}
		list($noOfRecords, $noOfRecordsDeleted) = $this->undoRecords($type, $moduleName);
		self::$bulkSaveMode = $previousBulkSaveMode;
		$viewer->assign('FOR_MODULE', $moduleName);
		$viewer->assign('MODULE', 'Import');
		$viewer->assign('TOTAL_RECORDS', $noOfRecords);
		$viewer->assign('DELETED_RECORDS_COUNT', $noOfRecordsDeleted);
		$viewer->view('ImportUndoResult.tpl', 'Import');
	}

	public function undoRecords($type, $moduleName)
	{
		$user = $request->getUser();
		$dbTableName = \App\Modules\Import\Models\Module::getDbTableName($user);
		$dataReader = (new \App\Db\Query())->select(['recordid'])
				->from($dbTableName)
				->where(['and', ['temp_status' => \App\Modules\Import\Actions\Data::IMPORT_RECORD_CREATED], ['not', ['recordid' => null]]])
				->createCommand()->query();
		$noOfRecords = $noOfRecordsDeleted = 0;
		while ($recordId = $dataReader->readColumn(0)) {
			if (\App\Records\Record::isExists($recordId)) {
				$recordModel = \App\Modules\Base\Models\Record::getInstanceById($recordId, $moduleName);
				if ($recordModel->isDeletable()) {
					$recordModel->delete();
					$noOfRecordsDeleted++;
				}
			}
			$noOfRecords++;
		}
		return [$noOfRecords, $noOfRecordsDeleted];
	}

	public function lastImportedRecords(\App\Http\Vtiger_Request $request)
	{
		$importList = new \App\Modules\Import\Views\ListView();
		$importList->process($request);
	}

	public function deleteMap(\App\Http\Vtiger_Request $request)
	{
		\App\Modules\Import\Views\Main::deleteMap($request);
	}

	public function clearCorruptedData(\App\Http\Vtiger_Request $request)
	{
		$user = $request->getUser();
		\App\Modules\Import\Models\Module::clearUserImportInfo($user);
		$this->importBasicStep($request);
	}

	public function cancelImport(\App\Http\Vtiger_Request $request)
	{
		$importId = $request->get('import_id');
		$user = $request->getUser();

		$importInfo = \App\Modules\Import\Actions\Queue::getImportInfoById($importId);
		if ($importInfo != null) {
			if ($importInfo['user_id'] == $user->id || $user->isAdminUser()) {
				$importUser = \App\Modules\Users\Models\Record::getInstanceById($importInfo['user_id'], 'Users');
				$importDataController = new \App\Modules\Import\Actions\Data($importInfo, $importUser);
				$importStatusCount = $importDataController->getImportStatusCount();
				$importDataController->finishImport();
				\App\Modules\Import\Views\Main::showResult($importInfo, $importStatusCount);
			}
		}
	}

	public function checkImportStatus(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$user = $request->getUser();
		$mode = $request->getMode();

		// Check if import on the module is locked
		$lockInfo = \App\Modules\Import\Actions\Lock::isLockedForModule($moduleName);
		if ($lockInfo != null) {
			$lockedBy = $lockInfo['userid'];
			if ($user->id != $lockedBy && !$user->isAdminUser()) {
				\App\Modules\Import\Helpers\Utils::showImportLockedError($lockInfo);
				throw new \App\Exceptions\NoPermitted('LBL_PERMISSION_DENIED');
			} else {
				if ($mode == 'continueImport' && $user->id == $lockedBy) {
					$importController = new \App\Modules\Import\Views\Main($request, $user);
					$importController->triggerImport(true);
				} else {
					$importInfo = \App\Modules\Import\Actions\Queue::getImportInfoById($lockInfo['importid']);
					$lockOwner = $user;
					if ($user->id != $lockedBy) {
						$lockOwner = \App\Modules\Users\Models\Record::getInstanceById($lockInfo['userid'], 'Users');
					}
					\App\Modules\Import\Views\Main::showImportStatus($importInfo, $lockOwner);
				}
				return;
			}
		}

		if (\App\Modules\Import\Models\Module::isUserImportBlocked($user)) {
			$importInfo = \App\Modules\Import\Actions\Queue::getUserCurrentImportInfo($user);
			if ($importInfo != null) {
				\App\Modules\Import\Views\Main::showImportStatus($importInfo, $user);
				return;
			} else {
				\App\Modules\Import\Helpers\Utils::showImportTableBlockedError($moduleName, $user);
				return;
			}
		}
		\App\Modules\Import\Models\Module::clearUserImportInfo($user);
	}
}
