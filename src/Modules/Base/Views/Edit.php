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

	public function process(\App\Http\Vtiger_Request $request)
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
		$viewer->assign('BLOCK_LIST', $moduleModel->getBlocks());
		$viewer->assign('CURRENTDATE', date('Y-n-j'));
		$viewer->assign('USER_MODEL', $request->getUser());
		$viewer->assign('APIADDRESS', \App\Modules\Settings\ApiAddress\Models\Module::getInstance('Settings:ApiAddress')->getConfig());
		$viewer->assign('APIADDRESS_ACTIVE', \App\Modules\Settings\ApiAddress\Models\Module::isActive());
		$viewer->assign('MAX_UPLOAD_LIMIT_MB', \App\Modules\Base\Helpers\Util::getMaxUploadSize());
		$viewer->assign('MAX_UPLOAD_LIMIT', vglobal('upload_maxsize'));
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
