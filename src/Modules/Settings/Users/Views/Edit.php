<?php

namespace App\Modules\Settings\Users\Views;


/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * *********************************************************************************** */

class Edit extends \App\Modules\Users\Views\PreferenceEdit
{

	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$currentUserModel = $request->getUser();
		$record = $request->get('record');
		
		// Admins can edit any user (active or inactive)
		if ($currentUserModel->isAdminUser() === true) {
			return true;
		}
		
		// Non-admins can only edit their own record if preferences are allowed
		if ($currentUserModel->get('id') == $record && \App\Core\AppConfig::security('SHOW_MY_PREFERENCES')) {
			// Check that the user being edited is active
			if (!empty($record)) {
				$recordModel = \App\Modules\Users\Models\Record::getInstanceById($record, $moduleName);
				if ($recordModel->get('status') != 'Active') {
					throw new \App\Exceptions\AppException('LBL_PERMISSION_DENIED');
				}
			}
			return true;
		}
		
		// Otherwise deny access
		throw new \App\Exceptions\AppException('LBL_PERMISSION_DENIED');
	}

	public function preProcess(\App\Http\Vtiger_Request $request, $display = true)
	{
		parent::preProcess($request, false);
		$viewer = $this->getViewer($request);
		$viewer->assign('IS_PREFERENCE', false);
		$viewer->assign('PARENT_MODULE', 'Settings');
		// MainLayout handles rendering, no separate preProcess template needed
	}

	public function getFooterScripts(\App\Http\Vtiger_Request $request)
	{
		$headerScriptInstances = parent::getFooterScripts($request);
		$moduleName = $request->getModule();

		$jsFileNames = array(
			'modules.Settings.Vtiger.resources.Index'
		);

		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		$headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);
		return $headerScriptInstances;
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		// Call parent to set up all the data
		$moduleName = $request->getModule();
		$recordId = $request->get('record');

		if (!empty($recordId)) {
			$recordModel = \App\Modules\Base\Models\Record::getInstanceById($recordId, $moduleName);
		} else {
			$recordModel = \App\Modules\Base\Models\Record::getCleanInstance($moduleName);
		}

		$recordStructureInstance = \App\Modules\Base\Models\RecordStructure::getInstanceFromRecordModel($recordModel, \App\Modules\Base\Models\RecordStructure::RECORD_STRUCTURE_MODE_EDIT);
		$dayStartPicklistValues = \App\Modules\Users\Models\Record::getDayStartsPicklistValues($recordStructureInstance->getStructure());

		$viewer = $this->getViewer($request);
		$viewer->assign("DAY_STARTS", \App\Utils\Json::encode($dayStartPicklistValues));
		$viewer->assign('IMAGE_DETAILS', $recordModel->getImageDetails());
		$viewer->assign('USER_MODEL', $request->getUser());

		// Now call the base Edit process which sets up more data and renders
		// But we need to render the Settings template
		$qualifiedModuleName = $request->getModule(false);
		
		// Get all the record structure and other data from base Edit
		$moduleModel = \App\Modules\Base\Models\Module::getInstance($moduleName);
		$fieldList = $moduleModel->getFields();
		$requestFieldList = array_intersect_key($request->getAll(), $fieldList);

		foreach ($requestFieldList as $fieldName => $fieldValue) {
			$fieldModel = $fieldList[$fieldName];
			if ($fieldModel->isEditable()) {
				$recordModel->set($fieldName, $fieldModel->getDBValue($fieldValue));
			}
		}
		
		$recordStructure = $recordStructureInstance->getStructure();
		$picklistDependencyDatasource = \App\Modules\PickList\DependencyPicklist::getPicklistDependencyDatasource($moduleName);

		$isRelationOperation = $request->get('relationOperation');
		$viewer->assign('IS_RELATION_OPERATION', $isRelationOperation);
		if ($isRelationOperation) {
			$sourceModule = $request->get('sourceModule');
			$sourceRecord = $request->get('sourceRecord');
			$viewer->assign('SOURCE_MODULE', $sourceModule);
			$viewer->assign('SOURCE_RECORD', $sourceRecord);
		}

		$viewer->assign('PICKIST_DEPENDENCY_DATASOURCE', \App\Modules\Base\Helpers\Util::toSafeHTML(\App\Utils\Json::encode($picklistDependencyDatasource)));
		$viewer->assign('MAPPING_RELATED_FIELD', \App\Modules\Base\Helpers\Util::toSafeHTML(\App\Utils\Json::encode($moduleModel->getValuesFromSource($request))));
		$viewer->assign('CURRENTDATE', \App\Modules\Base\UiTypes\Date::getDisplayDateValue(date('Y-n-j')));
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('SINGLE_MODULE', 'SINGLE_' . $moduleName);
		$viewer->assign('MODULE_MODEL', $moduleModel);
		$viewer->assign('RECORD_ID', $recordId);
		$viewer->assign('RECORD_STRUCTURE_MODEL', $recordStructureInstance);
		$viewer->assign('RECORD_STRUCTURE', $recordStructure);
		$viewer->assign('BLOCK_LIST', $moduleModel->getBlocks());
		$viewer->assign('MODE', '');
		$viewer->assign('IS_AJAX_ENABLED', $this->isAjaxEnabled($recordModel));
		$viewer->assign('IS_PREFERENCE', false);
		$viewer->assign('PARENT_MODULE', 'Settings');
		
		// Render using Settings template which extends MainLayout
		$viewer->view('EditView.tpl', $qualifiedModuleName);
	}

	/**
	 * Function to get Ajax is enabled or not
	 * @param \App\Modules\Base\Models\Record record model
	 * @return bool true/false
	 */
	public function isAjaxEnabled($recordModel)
	{
		if ($recordModel->get('status') != 'Active') {
			return false;
		}
		return $recordModel->isEditable();
	}
}
