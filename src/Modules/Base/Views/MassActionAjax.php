<?php

namespace App\Modules\Base\Views;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */


use App\Http\Vtiger_Request;

class MassActionAjax extends \App\Modules\Base\Views\Index
{

	public function __construct()
	{
		parent::__construct();
		$this->exposeMethod('showMassEditForm');
		$this->exposeMethod('showAddCommentForm');
		$this->exposeMethod('showSendSMSForm');
		$this->exposeMethod('showDuplicatesSearchForm');
		$this->exposeMethod('transferOwnership');
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$mode = $request->get('mode');
		if (!empty($mode)) {
			$this->invokeExposedMethod($mode, $request);
			return;
		}
	}

	/**
	 * Function returns the mass edit form
	 * @param \App\Http\Vtiger_Request $request
	 */
	public function showMassEditForm(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$cvId = $request->get('viewname');
		$selectedIds = $request->get('selected_ids');
		$excludedIds = $request->get('excluded_ids');

		$viewer = $this->getViewer($request);

		$moduleModel = \App\Modules\Base\Models\Module::getInstance($moduleName);
		$recordStructureInstance = \App\Modules\Base\Models\RecordStructure::getInstanceForModule($moduleModel, \App\Modules\Base\Models\RecordStructure::RECORD_STRUCTURE_MODE_MASSEDIT);
		$fieldInfo = [];
		$fieldList = $moduleModel->getFields();
		foreach ($fieldList as $fieldName => $fieldModel) {
			$fieldInfo[$fieldName] = $fieldModel->getFieldInfo();
		}
		$picklistDependencyDatasource = \App\Modules\PickList\DependencyPicklist::getPicklistDependencyDatasource($moduleName);

		$viewer->assign('PICKIST_DEPENDENCY_DATASOURCE', \App\Utils\Json::encode($picklistDependencyDatasource));
		$viewer->assign('CURRENTDATE', date('Y-n-j'));
		$viewer->assign('MODE', 'massedit');
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('CVID', $cvId);
		$viewer->assign('SELECTED_IDS', $selectedIds);
		$viewer->assign('EXCLUDED_IDS', $excludedIds);
		$viewer->assign('RECORD_STRUCTURE_MODEL', $recordStructureInstance);
		$viewer->assign('MODULE_MODEL', $moduleModel);
		$viewer->assign('MASS_EDIT_FIELD_DETAILS', $fieldInfo);
		$viewer->assign('RECORD_STRUCTURE', $recordStructureInstance->getStructure());
		$viewer->assign('USER_MODEL', $request->getUser());
		$viewer->assign('MODULE_MODEL', $moduleModel);
		$viewer->assign('MAPPING_RELATED_FIELD', \App\Utils\Json::encode(\App\Core\ModuleHierarchy::getRelationFieldByHierarchy($moduleName)));
		$searchKey = $request->get('search_key');
		$searchValue = $request->get('search_value');
		$operator = $request->get('operator');
		if (!empty($operator)) {
			$viewer->assign('OPERATOR', $operator);
			$viewer->assign('ALPHABET_VALUE', $searchValue);
			$viewer->assign('SEARCH_KEY', $searchKey);
		}
		$searchParams = $request->get('search_params');
		if (!empty($searchParams)) {
			$viewer->assign('SEARCH_PARAMS', $searchParams);
		}

		echo $viewer->view('MassEditForm.tpl', $moduleName, true);
	}

	/**
	 * Function returns the Add Comment form
	 * @param \App\Http\Vtiger_Request $request
	 */
	public function showAddCommentForm(\App\Http\Vtiger_Request $request)
	{
		$sourceModule = $request->getModule();
		$moduleName = 'ModComments';
		$cvId = $request->get('viewname');
		$selectedIds = $request->get('selected_ids');
		$excludedIds = $request->get('excluded_ids');

		$viewer = $this->getViewer($request);
		$viewer->assign('SOURCE_MODULE', $sourceModule);
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('CVID', $cvId);
		$viewer->assign('SELECTED_IDS', $selectedIds);
		$viewer->assign('EXCLUDED_IDS', $excludedIds);
		$viewer->assign('USER_MODEL', $request->getUser());

		$searchKey = $request->get('search_key');
		$searchValue = $request->get('search_value');
		$operator = $request->get('operator');
		if (!empty($operator)) {
			$viewer->assign('OPERATOR', $operator);
			$viewer->assign('ALPHABET_VALUE', $searchValue);
			$viewer->assign('SEARCH_KEY', $searchKey);
		}

		$searchParams = $request->get('search_params');
		if (!empty($searchParams)) {
			$viewer->assign('SEARCH_PARAMS', $searchParams);
		}

		echo $viewer->view('AddCommentForm.tpl', $moduleName, true);
	}

	/**
	 * Function shows form that will lets you send SMS
	 * @param \App\Http\Vtiger_Request $request
	 */
	public function showSendSMSForm(\App\Http\Vtiger_Request $request)
	{

		$sourceModule = $request->getModule();
		$moduleName = 'SMSNotifier';
		$selectedIds = $this->getRecordsListFromRequest($request);
		$excludedIds = $request->get('excluded_ids');
		$cvId = $request->get('viewname');

		$user = $request->getUser();
		$moduleModel = \App\Modules\Base\Models\Module::getInstance($sourceModule);
		$phoneFields = $moduleModel->getFieldsByType('phone');
		$viewer = $this->getViewer($request);

		if (count($selectedIds) == 1) {
			$recordId = $selectedIds[0];
			$selectedRecordModel = \App\Modules\Base\Models\Record::getInstanceById($recordId, $sourceModule);
			$viewer->assign('SINGLE_RECORD', $selectedRecordModel);
		}
		$viewer->assign('VIEWNAME', $cvId);
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('SOURCE_MODULE', $sourceModule);
		$viewer->assign('SELECTED_IDS', $selectedIds);
		$viewer->assign('EXCLUDED_IDS', $excludedIds);
		$viewer->assign('USER_MODEL', $user);
		$viewer->assign('PHONE_FIELDS', $phoneFields);

		$searchKey = $request->get('search_key');
		$searchValue = $request->get('search_value');
		$operator = $request->get('operator');
		if (!empty($operator)) {
			$viewer->assign('OPERATOR', $operator);
			$viewer->assign('ALPHABET_VALUE', $searchValue);
			$viewer->assign('SEARCH_KEY', $searchKey);
		}

		$searchParams = $request->get('search_params');
		if (!empty($searchParams)) {
			$viewer->assign('SEARCH_PARAMS', $searchParams);
		}

		echo $viewer->view('SendSMSForm.tpl', $moduleName, true);
	}

	/**
	 * Function returns the record Ids selected in the current filter
	 * @param \App\Http\Vtiger_Request $request
	 * @return integer
	 */
	public function getRecordsListFromRequest(\App\Http\Vtiger_Request $request, $module = false)
	{
		$cvId = $request->get('viewname');
		$selectedIds = $request->get('selected_ids');
		$excludedIds = $request->get('excluded_ids');
		if (empty($module)) {
			$module = $request->getModule();
		}
		if (!empty($selectedIds) && $selectedIds != 'all') {
			if (!empty($selectedIds) && count($selectedIds) > 0) {
				return $selectedIds;
			}
		}

		$sourceRecord = $request->get('sourceRecord');
		$sourceModule = $request->get('sourceModule');
		if ($sourceRecord && $sourceModule) {
			$sourceRecordModel = \App\Modules\Base\Models\Record::getInstanceById($sourceRecord, $sourceModule);
			return $sourceRecordModel->getSelectedIdsList($module, $excludedIds);
		}

		$customViewModel = \App\Modules\CustomView\Models\Record::getInstanceById($cvId);
		if ($customViewModel) {
			$searchKey = $request->get('search_key');
			$searchValue = $request->get('search_value');
			$operator = $request->get('operator');
			if (!empty($operator)) {
				$customViewModel->set('operator', $operator);
				$customViewModel->set('search_key', $searchKey);
				$customViewModel->set('search_value', $searchValue);
			}
			$customViewModel->set('search_params', $request->get('search_params'));
			return $customViewModel->getRecordIds($excludedIds, $module);
		}
	}

	/**
	 * Function shows the List of Mail Merge Templates
	 * @param \App\Http\Vtiger_Request $request
	 */
	public function showMailMergeTemplates(\App\Http\Vtiger_Request $request)
	{
		$selectedIds = $request->get('selected_ids');
		$excludedIds = $request->get('excluded_ids');
		$cvId = $request->get('viewname');
		$module = $request->getModule();
		// TODO: Settings_MailMerge_Record_Model class doesn't exist - needs implementation
		// $templates = Settings_MailMerge_Record_Model::getByModule($module);
		$templates = [];

		$viewer = $this->getViewer($request);
		$viewer->assign('TEMPLATES', $templates);
		$viewer->assign('SELECTED_IDS', $selectedIds);
		$viewer->assign('EXCLUDED_IDS', $excludedIds);
		$viewer->assign('VIEWNAME', $cvId);
		$viewer->assign('MODULE', $module);

		return $viewer->view('showMergeTemplates.tpl', $module);
	}

	/**
	 * Function shows the duplicate search form
	 * @param \App\Http\Vtiger_Request $request
	 */
	public function showDuplicatesSearchForm(\App\Http\Vtiger_Request $request)
	{
		$module = $request->getModule();
		$moduleModel = \App\Modules\Base\Models\Module::getInstance($module);
		$fields = $moduleModel->getFields();

		$viewer = $this->getViewer($request);
		$viewer->assign('MODULE', $module);
		$viewer->assign('FIELDS', $fields);
		$viewer->view('showDuplicateSearch.tpl', $module);
	}

	public function transferOwnership(\App\Http\Vtiger_Request $request)
	{
		$module = $request->getModule();
		$transferModel = \App\Modules\Base\Models\TransferOwnership::getInstance($module);

		$viewer = $this->getViewer($request);
		$viewer->assign('MODULE', $module);
		$viewer->assign('REL_BY_FIELDS', $transferModel->getRelationsByFields());
		$viewer->assign('REL_BY_RELATEDLIST', $transferModel->getRelationsByRelatedList());
		$viewer->assign('SKIP_MODULES', $transferModel->getSkipModules());
		$viewer->assign('USER_MODEL', $request->getUser());
		$viewer->view('TransferRecordOwnership.tpl', $module);
	}
}
