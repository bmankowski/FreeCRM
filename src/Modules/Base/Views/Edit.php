<?php


namespace App\Modules\Base\Views;

use App\Http\Vtiger_Request;
/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * ********************************************************************************** */

class Edit extends \App\Modules\Base\Views\Index
{

	protected $record = false;

	public function __construct()
	{
		parent::__construct();
	}

	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$record = $request->get('record');

		if (!empty($record)) {
			$recordModel = $this->record ? $this->record : \App\Modules\Base\Models\Record::getInstanceById($record, $moduleName);
			$isPermited = $recordModel->isEditable() || ($request->getBoolean('isDuplicate') === true && $recordModel->isCreateable() && $recordModel->isViewable());
		} else {
			$recordModel = \App\Modules\Base\Models\Record::getCleanInstance($moduleName);
			$isPermited = $recordModel->isCreateable();
		}
		if (!$isPermited) {
			throw new \App\Exceptions\NoPermittedToRecord('LBL_NO_PERMISSIONS_FOR_THE_RECORD');
		}
	}

	public function getBreadcrumbTitle(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		if ($request->has('isDuplicate')) {
			$pageTitle = \App\Runtime\Vtiger_Language_Handler::translate('LBL_VIEW_DUPLICATE', $moduleName);
		} elseif ($request->has('record')) {
			$pageTitle = \App\Runtime\Vtiger_Language_Handler::translate('LBL_VIEW_EDIT', $moduleName);
		} else {
			$pageTitle = \App\Runtime\Vtiger_Language_Handler::translate('LBL_VIEW_CREATE', $moduleName);
		}
		return $pageTitle;
	}

	public function preProcess(\App\Http\Vtiger_Request $request, $display = true)
	{
		parent::preProcess($request, false);
		$this->assignEditViewData($request);
	}

	protected function assignEditViewData(\App\Http\Vtiger_Request $request)
	{
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		$record = $request->get('record');
		if (!empty($record) && $request->getBoolean('isDuplicate') === true) {
			$viewer->assign('MODE', '');
			$viewer->assign('RECORD_ID', '');
			$recordModel = $this->getDuplicate($record, $moduleName);
		} else if (!empty($record)) {
			$recordModel = $this->record ? $this->record : \App\Modules\Base\Models\Record::getInstanceById($record, $moduleName);
			$viewer->assign('MODE', 'edit');
			$viewer->assign('RECORD_ID', $record);
		} else {
			$recordModel = \App\Modules\Base\Models\Record::getCleanInstance($moduleName);
			$referenceId = $request->get('reference_id');
			if ($referenceId) {
				$parentRecordModel = \App\Modules\Base\Models\Record::getInstanceById($referenceId);
				$recordModel->setRecordFieldValues($parentRecordModel);
			}
			$viewer->assign('MODE', '');
			$viewer->assign('RECORD_ID', '');
		}
		if (!$this->record) {
			$this->record = $recordModel;
		}

		$editModel = \App\Modules\Base\Models\EditView::getInstance($moduleName, $record);
		$editViewLinkParams = ['MODULE' => $moduleName, 'RECORD' => $record];
		$detailViewLinks = $editModel->getEditViewLinks($editViewLinkParams);
		$viewer->assign('EDITVIEW_LINKS', $detailViewLinks);

		$moduleModel = $recordModel->getModule();
		$fieldList = $moduleModel->getFields();
		$requestFieldList = array_intersect_key($request->getAll(), $fieldList);

		foreach ($requestFieldList as $fieldName => $fieldValue) {
			$fieldModel = $fieldList[$fieldName];
			$specialField = false;
			// We collate date and time part together in the EditView UI handling 
			// so a bit of special treatment is required if we come from QuickCreate 
			if ($moduleName == 'Calendar' && empty($record) && $fieldName == 'time_start' && !empty($fieldValue)) {
				$specialField = true;
				// Convert the incoming user-picked time to GMT time 
				// which will get re-translated based on user-time zone on EditForm 
				$fieldValue = \App\Fields\DateTimeField::convertToDBTimeZone($fieldValue)->format("H:i");
			}

			if ($moduleName == 'Calendar' && empty($record) && $fieldName == 'date_start' && !empty($fieldValue)) {
				$startTime = \App\Modules\Base\UiTypes\Time::getTimeValueWithSeconds($requestFieldList['time_start']);
				$startDateTime = \App\Modules\Base\UiTypes\Datetime::getDBDateTimeValue($fieldValue . " " . $startTime);
				list($startDate, $startTime) = explode(' ', $startDateTime);
				$fieldValue = \App\Modules\Base\UiTypes\Date::getDisplayDateValue($startDate);
			}
			if ($fieldModel->isEditable() || $specialField) {
				$recordModel->set($fieldName, $fieldModel->getDBValue($fieldValue));
			}
		}
		$recordStructureInstance = \App\Modules\Base\Models\RecordStructure::getInstanceFromRecordModel($recordModel, \App\Modules\Base\Models\RecordStructure::RECORD_STRUCTURE_MODE_EDIT);
		$recordStructure = $recordStructureInstance->getStructure();
		$picklistDependencyDatasource = \App\Modules\PickList\DependencyPicklist::getPicklistDependencyDatasource($moduleName);

		$isRelationOperation = $request->get('relationOperation');
		//if it is relation edit
		$viewer->assign('IS_RELATION_OPERATION', $isRelationOperation);
		if ($isRelationOperation) {
			$sourceModule = $request->get('sourceModule');
			$sourceRecord = $request->get('sourceRecord');

			$viewer->assign('SOURCE_MODULE', $sourceModule);
			$viewer->assign('SOURCE_RECORD', $sourceRecord);
			$sourceRelatedField = $moduleModel->getValuesFromSource($request);
			foreach ($recordStructure as &$block) {
				foreach ($sourceRelatedField as $field => &$value) {
					if (isset($block[$field])) {
						$fieldvalue = $block[$field]->get('fieldvalue');
						if (empty($fieldvalue)) {
							$block[$field]->set('fieldvalue', $value);
						}
					}
				}
			}
		}
		$viewer->assign('PICKIST_DEPENDENCY_DATASOURCE', \App\Json::encode($picklistDependencyDatasource));
		$viewer->assign('MAPPING_RELATED_FIELD', \App\Json::encode(\App\ModuleHierarchy::getRelationFieldByHierarchy($moduleName)));
		$viewer->assign('RECORD_STRUCTURE_MODEL', $recordStructureInstance);
		$viewer->assign('RECORD_STRUCTURE', $recordStructure);
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('MODULE_TYPE', $moduleModel->getModuleType());
		$viewer->assign('RECORD', $recordModel);
		if (method_exists($recordModel, 'getBaseCurrencyDetails')) {
			$baseCurrencyDetails = $recordModel->getBaseCurrencyDetails();
			if (!empty($baseCurrencyDetails['currencyid'])) {
				$viewer->assign('BASE_CURRENCY_ID', $baseCurrencyDetails['currencyid']);
				$viewer->assign('BASE_CURRENCY_NAME', 'curname' . $baseCurrencyDetails['currencyid']);
			}
			if (!empty($baseCurrencyDetails['symbol'])) {
				$viewer->assign('BASE_CURRENCY_SYMBOL', $baseCurrencyDetails['symbol']);
			}
		}
		$viewer->assign('BLOCK_LIST', $moduleModel->getBlocks());
		$viewer->assign('CURRENTDATE', date('Y-n-j'));
		$viewer->assign('USER_MODEL', $request->getUser());
		$viewer->assign('APIADDRESS', \App\Modules\Settings\ApiAddress\Models\Module::getInstance('Settings:ApiAddress')->getConfig());
		$viewer->assign('APIADDRESS_ACTIVE', \App\Modules\Settings\ApiAddress\Models\Module::isActive());
		$viewer->assign('MAX_UPLOAD_LIMIT_MB', \App\Modules\Base\Helpers\Util::getMaxUploadSize());
		$viewer->assign('MAX_UPLOAD_LIMIT', \App\AppConfig::main('upload_maxsize'));
		
		// Prepare inventory data if module supports inventory
		if ($moduleModel->isInventory()) {
			$this->prepareInventoryData($viewer, $moduleName, $recordModel);
		}
	}

	/**
	 * Prepare data for EditViewInventory template
	 * Moves function calls from templates to controller for better MVC separation
	 */
	protected function prepareInventoryData($viewer, $moduleName, $recordModel)
	{
		$inventoryField = \App\Modules\Base\Models\InventoryField::getInstance($moduleName);
		$fields = $inventoryField->getFields(true);
		
		// getFields() can return null if table doesn't exist or there's an error
		if ($fields === null || !is_array($fields) || count($fields) == 0) {
			// No inventory fields configured, skip inventory data preparation
			return;
		}
		
		$viewer->assign('INVENTORY_FIELD', $inventoryField);
		$viewer->assign('FIELDS', $fields); // Template uses FIELDS, not INVENTORY_FIELDS
		$viewer->assign('DISCOUNTS_CONFIG', \App\Modules\Base\Models\Inventory::getDiscountsConfig());
		$viewer->assign('TAXS_CONFIG', \App\Modules\Base\Models\Inventory::getTaxesConfig());
		$viewer->assign('BASE_CURRENCY', \App\Modules\Base\Helpers\Util::getBaseCurrency());
		
		$columns = $inventoryField->getColumns();
		// Ensure columns is always an array
		if (!is_array($columns)) {
			$columns = [];
		}
		$inventoryRows = $recordModel->getInventoryData();
		// Ensure inventoryRows is always an array, even if empty
		if (!is_array($inventoryRows)) {
			$inventoryRows = [];
		}
		$mainParams = $inventoryField->getMainParams($fields[1]);
		// Ensure mainParams is always an array with expected structure
		if (!is_array($mainParams)) {
			$mainParams = ['modules' => [], 'limit' => 0];
		}
		if (!isset($mainParams['modules'])) {
			$mainParams['modules'] = [];
		}
		if (!isset($mainParams['limit'])) {
			$mainParams['limit'] = 0;
		}
		
		$viewer->assign('COLUMNS', $columns); // Template uses COLUMNS
		$viewer->assign('INVENTORY_ROWS', $inventoryRows);
		$viewer->assign('MAIN_PARAMS', $mainParams); // Template uses MAIN_PARAMS
		$viewer->assign('COUNT_FIELDS0', count($fields[0])); // Template uses COUNT_FIELDS0
		$viewer->assign('COUNT_FIELDS1', count($fields[1])); // Template uses COUNT_FIELDS1
		$viewer->assign('COUNT_FIELDS2', count($fields[2])); // Template uses COUNT_FIELDS2
		
		// Prepare currency symbol and rate if currency column exists
		if (in_array("currency", $columns)) {
			if (count($inventoryRows) > 0 && !empty($inventoryRows[0]['currency'])) {
				$currency = $inventoryRows[0]['currency'];
			} else {
				$baseCurrency = \App\Modules\Base\Helpers\Util::getBaseCurrency();
				$currency = $baseCurrency['id'];
			}
			$viewer->assign('CURRENCY', $currency);
			$viewer->assign('CURRENCY_SYMBOLAND', \vtlib\Functions::getCurrencySymbolandRate($currency));
		}
		
		$viewer->assign('INVENTORY_ITEMS_NO', count($inventoryRows));
		
		// Prepare CRMEntity instances for main modules
		$crmEntities = [];
		$wysiwygTypes = [];
		if (is_array($mainParams['modules']) && !empty($mainParams['modules'])) {
			foreach ($mainParams['modules'] as $mainModule) {
				$crmEntities[$mainModule] = \App\CRMEntity::getInstance($mainModule);
				$wysiwygTypes[$mainModule] = $inventoryField->isWysiwygType($mainModule);
			}
		}
		$viewer->assign('INVENTORY_CRM_ENTITIES', $crmEntities);
		$viewer->assign('INVENTORY_WYSIWYG_TYPES', $wysiwygTypes);
		
		// Pre-calculate reference field
		$viewer->assign('INVENTORY_REFERENCE_FIELD', $inventoryField->getReferenceField());
		
		// Pre-calculate summary values for footer
		$summaryValues = [];
		foreach ($fields[1] as $field) {
			if ($field->isSummary()) {
				$sum = 0;
				foreach ($inventoryRows as $itemValue) {
					$sum += ($itemValue[$field->get('columnname')] ?? 0);
				}
				$summaryValues[$field->getName()] = \App\Fields\CurrencyField::convertToUserFormat($sum, null, true);
			}
		}
		$viewer->assign('INVENTORY_SUMMARY_VALUES', $summaryValues);
		
		// Pre-assign default inventory data fields
		$viewer->assign('ITEM_DATA', $recordModel->getInventoryDefaultDataFields());
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		// Data already assigned in preProcess, just render
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		$viewer->view('EditView.tpl', $moduleName);
	}

	public function getDuplicate($record, $moduleName)
	{
		$recordModel = $this->record ? $this->record : \App\Modules\Base\Models\Record::getInstanceById($record, $moduleName);
		$recordModel->set('id', '');
		//While Duplicating record, If the related record is deleted then we are removing related record info in record model
		$mandatoryFieldModels = $recordModel->getModule()->getMandatoryFieldModels();
		foreach ($mandatoryFieldModels as $fieldModel) {
			if ($fieldModel->isReferenceField()) {
				$fieldName = $fieldModel->get('name');
				if (!\App\Record::isExists($recordModel->get($fieldName))) {
					$recordModel->set($fieldName, '');
				}
			}
		}
		return $recordModel;
	}

	/**
	 * Function to get the list of Script models to be included
	 * @param \App\Http\Vtiger_Request $request
	 * @return <Array> - List of \App\Modules\Base\Models\JsScript instances
	 */
	public function getFooterScripts(\App\Http\Vtiger_Request $request)
	{
		$parentScript = parent::getFooterScripts($request);

		$moduleName = $request->getModule();
		if (\App\Modules\Base\Models\Module::getInstance($moduleName)->isInventory()) {
			$fileNames = [
				'modules.Base.resources.Inventory',
				'modules.' . $moduleName . '.resources.Inventory',
			];
			$scriptInstances = $this->checkAndConvertJsScripts($fileNames);
			$parentScript = array_merge($parentScript, $scriptInstances);
		}
		return $parentScript;
	}
}
