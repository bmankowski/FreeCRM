<?php

namespace App\Modules\Base\Views;

/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * ********************************************************************************** */


use App\Http\Vtiger_Request;
class Popup  extends \App\Modules\Base\Views\Index
{

	protected array $listViewEntries = [];
	protected array $listViewHeaders = [];

	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		$currentUserPrivilegesModel = \App\Modules\Users\Models\Privileges::getCurrentUserPrivilegesModel();
		if (!$currentUserPrivilegesModel->hasModulePermission($request->getModule())) {
			throw new \App\Exceptions\NoPermitted('LBL_PERMISSION_DENIED');
		}
	}

	/**
	 * Function returns the module name for which the popup should be initialized
	 * @param Vtiger_request $request
	 * @return string
	 */
	public function getModule(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		return $moduleName;
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$viewer = $this->getViewer($request);
		$moduleName = $this->getModule($request);

		$this->initializeListViewContents($request, $viewer);
		$viewer->assign('TRIGGER_EVENT_NAME', $request->get('triggerEventName'));
		$viewer->assign('FOOTER_SCRIPTS', $this->getFooterScripts($request));
		$viewer->view('Popup.tpl', $moduleName);
	}

	/**
	 * Function to get the list of Script models to be included
	 * @param \App\Http\Vtiger_Request $request
	 * @return array - List of \App\Modules\Base\Models\JsScript instances
	 */
	public function getFooterScripts(\App\Http\Vtiger_Request $request)
	{
		$headerScriptInstances = parent::getFooterScripts($request);
		$moduleName = $request->getModule();

		$jsFileNames = array(
			'libraries.bootstrap.js.eternicode-bootstrap-datepicker.js.bootstrap-datepicker',
			'~libraries/bootstrap/js/eternicode-bootstrap-datepicker/js/locales/bootstrap-datepicker.' . \App\Runtime\Vtiger_Language_Handler::getShortLanguageName() . '.js',
			'~libraries/jquery/clockpicker/jquery-clockpicker.min.js',
			'modules.Base.resources.BaseList',
			"modules.$moduleName.resources.BaseList",
			'modules.Base.resources.Popup',
			"modules.$moduleName.resources.Popup",
			'libraries.jquery.jquery_windowmsg',
			'modules.Base.resources.validator.BaseValidator',
			'modules.Base.resources.validator.FieldValidator',
			"modules.$moduleName.resources.validator.FieldValidator"
		);
		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		$headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);
		return $headerScriptInstances;
	}
	/*
	 * Function to initialize the required data in smarty to display the List View Contents
	 */

	public function initializeListViewContents(\App\Http\Vtiger_Request $request, \App\Runtime\CRM_Viewer $viewer)
	{
		$moduleName = $this->getModule($request);
		$cvId = $request->get('cvid');
		$pageNumber = $request->get('page');
		$orderBy = $request->get('orderby');
		$sortOrder = $request->get('sortorder');
		$sourceModule = $request->get('src_module');
		$sourceField = $request->get('src_field');
		$sourceRecord = $request->get('src_record');
		$searchKey = $request->get('search_key');
		$searchValue = $request->get('search_value');
		$currencyId = $request->get('currency_id');
		$relatedParentModule = $request->get('related_parent_module');
		$relatedParentId = $request->get('related_parent_id');
		$filterFields = $request->get('filterFields');

		//To handle special operation when selecting record from Popup
		$getUrl = $request->get('get_url');

		//Check whether the request is in multi select mode
		$multiSelectMode = $request->get('multi_select');
		if (empty($multiSelectMode)) {
			$multiSelectMode = false;
		}

		if (empty($cvId)) {
			$cvId = '0';
		}
		if (empty($pageNumber)) {
			$pageNumber = '1';
		}

		$pagingModel = new \App\Modules\Base\Models\Paging();
		$pagingModel->set('page', $pageNumber);
		if ($this instanceof \App\Modules\Base\Views\PopupAjax)
			$pagingModel->set('noLimit', true);

		$moduleModel = \App\Modules\Base\Models\Module::getInstance($moduleName);
		$recordStructureInstance = \App\Modules\Base\Models\RecordStructure::getInstanceForModule($moduleModel);

		if (!\App\Records\Record::isExists($relatedParentId)) {
			$relatedParentModule = '';
			$relatedParentId = '';
		}
		if (!empty($relatedParentModule) && !empty($relatedParentId)) {
			$parentRecordModel = \App\Modules\Base\Models\Record::getInstanceById($relatedParentId, $relatedParentModule);
			$listViewModel = \App\Modules\Base\Models\RelationListView::getInstance($parentRecordModel, $moduleName, $label);
		} else {
			$listViewModel = \App\Modules\Base\Models\ListView::getInstanceForPopup($moduleName, $sourceModule);
		}
		if (empty($orderBy) && empty($sortOrder)) {
			$moduleInstance = \App\Core\CRMEntity::getInstance($moduleName);
			$orderBy = $moduleInstance->default_order_by;
			$sortOrder = $moduleInstance->default_sort_order;
		}
		if (!empty($orderBy)) {
			$listViewModel->set('orderby', $orderBy);
			$listViewModel->set('sortorder', $sortOrder);
		}
		if (!empty($filterFields)) {
			$listViewModel->set('filterFields', $filterFields);
		}
		if (!empty($sourceModule)) {
			$listViewModel->set('src_module', $sourceModule);
			$listViewModel->set('src_field', $sourceField);
			$listViewModel->set('src_record', $sourceRecord);
		}
		if ((!empty($searchKey)) && (!empty($searchValue))) {
			$listViewModel->set('search_key', $searchKey);
			$listViewModel->set('search_value', $searchValue);
		}
		$searchParmams = $request->get('search_params');
		if (empty($searchParmams)) {
			$searchParmams = [];
		}
		$transformedSearchParams = $listViewModel->getQueryGenerator()->parseBaseSearchParamsToCondition($searchParmams);
		$listViewModel->set('search_params', $transformedSearchParams);
		//To make smarty to get the details easily accesible
		foreach ($searchParmams as $fieldListGroup) {
			foreach ($fieldListGroup as $fieldSearchInfo) {
				$fieldSearchInfo['searchValue'] = $fieldSearchInfo[2];
				$fieldSearchInfo['fieldName'] = $fieldName = $fieldSearchInfo[0];
				$searchParmams[$fieldName] = $fieldSearchInfo;
			}
		}
		if (!empty($relatedParentModule) && !empty($relatedParentId)) {
			$this->listViewHeaders = $listViewModel->getHeaders();
			$this->listViewEntries = $listViewModel->getEntries($pagingModel);
			if (count($this->listViewEntries) > 0) {
				$parentRelatedRecords = true;
			}
		} else {
			$this->listViewHeaders = $listViewModel->getListViewHeaders();
			$this->listViewEntries = $listViewModel->getListViewEntries($pagingModel);
		}

		// If there are no related records with parent module then, we should show all the records
		if (empty($parentRelatedRecords) && !empty($relatedParentModule) && !empty($relatedParentId)) {
			$relatedParentModule = null;
			$relatedParentId = null;
			$listViewModel = \App\Modules\Base\Models\ListView::getInstanceForPopup($moduleName, $sourceModule);
			$listViewModel->set('search_params', $transformedSearchParams);
			if (!empty($orderBy)) {
				$listViewModel->set('orderby', $orderBy);
				$listViewModel->set('sortorder', $sortOrder);
			}
			if (!empty($sourceModule)) {
				$listViewModel->set('src_module', $sourceModule);
				$listViewModel->set('src_field', $sourceField);
				$listViewModel->set('src_record', $sourceRecord);
			}
			if ((!empty($searchKey)) && (!empty($searchValue))) {
				$listViewModel->set('search_key', $searchKey);
				$listViewModel->set('search_value', $searchValue);
			}
			$this->listViewHeaders = $listViewModel->getListViewHeaders();
			$this->listViewEntries = $listViewModel->getListViewEntries($pagingModel);
		}
		// End
		$noOfEntries = count($this->listViewEntries);
		if (empty($sortOrder)) {
			$sortOrder = 'ASC';
		}
		if ($sortOrder == 'ASC') {
			$nextSortOrder = 'DESC';
			$sortImage = 'downArrowSmall.png';
		} else {
			$nextSortOrder = "ASC";
			$sortImage = 'upArrowSmall.png';
		}
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('RELATED_MODULE', $moduleName);
		$viewer->assign('MODULE_NAME', $moduleName);

		$viewer->assign('SOURCE_MODULE', $sourceModule);
		$viewer->assign('SOURCE_FIELD', $sourceField);
		$viewer->assign('SOURCE_RECORD', $sourceRecord);
		$viewer->assign('RELATED_PARENT_MODULE', $relatedParentModule);
		$viewer->assign('RELATED_PARENT_ID', $relatedParentId);

		$viewer->assign('SEARCH_KEY', $searchKey);
		$viewer->assign('SEARCH_VALUE', $searchValue);

		$viewer->assign('ORDER_BY', $orderBy);
		$viewer->assign('SORT_ORDER', $sortOrder);
		$viewer->assign('NEXT_SORT_ORDER', $nextSortOrder);
		$viewer->assign('SORT_IMAGE', $sortImage);
		$viewer->assign('GETURL', $getUrl);
		$viewer->assign('CURRENCY_ID', $currencyId);

		$viewer->assign('RECORD_STRUCTURE_MODEL', $recordStructureInstance);
		$viewer->assign('RECORD_STRUCTURE', $recordStructureInstance->getStructure());

		$viewer->assign('PAGING_MODEL', $pagingModel);
		$viewer->assign('PAGE_NUMBER', $pageNumber);

		$viewer->assign('LISTVIEW_ENTRIES_COUNT', $noOfEntries);
		$viewer->assign('LISTVIEW_HEADERS', $this->listViewHeaders);
		$viewer->assign('LISTVIEW_ENTRIES', $this->listViewEntries);

		if (\App\Core\AppConfig::performance('LISTVIEW_COMPUTE_PAGE_COUNT')) {
			if (!$this->listViewCount) {
				$this->listViewCount = $listViewModel->getListViewCount();
			}
			$totalCount = $this->listViewCount;
			$pageLimit = $pagingModel->getPageLimit();
			$pageCount = ceil((int) $totalCount / (int) $pageLimit);

			if ($pageCount == 0) {
				$pageCount = 1;
			}
			$viewer->assign('PAGE_COUNT', $pageCount);
			$viewer->assign('LISTVIEW_COUNT', $totalCount);
		}

		$viewer->assign('MULTI_SELECT', $multiSelectMode);
		$viewer->assign('CURRENT_USER_MODEL', $request->getUser());
		// Ensure search details exist for all headers to avoid undefined index notices in templates
		if (is_array($this->listViewHeaders)) {
			foreach ($this->listViewHeaders as $header) {
				$headerName = $header->getName();
				if (!isset($searchParmams[$headerName])) {
					$searchParmams[$headerName] = ['searchValue' => '', 'fieldName' => $headerName];
				}
			}
		}
		$viewer->assign('SEARCH_DETAILS', $searchParmams);
		// Enable the switch button block in popup actions; it will be disabled automatically if no related parent id
		$viewer->assign('SWITCH', true);
	}

	/**
	 * Function to get listView count
	 * @param \App\Http\Vtiger_Request $request
	 */
	public function getListViewCount(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $this->getModule($request);
		$sourceModule = $request->get('src_module');
		$sourceField = $request->get('src_field');
		$sourceRecord = $request->get('src_record');
		$orderBy = $request->get('orderby');
		$sortOrder = $request->get('sortorder');
		$currencyId = $request->get('currency_id');

		$searchKey = $request->get('search_key');
		$searchValue = $request->get('search_value');

		$relatedParentModule = $request->get('related_parent_module');
		$relatedParentId = $request->get('related_parent_id');

		if (!empty($relatedParentModule) && !empty($relatedParentId)) {
			$parentRecordModel = \App\Modules\Base\Models\Record::getInstanceById($relatedParentId, $relatedParentModule);
			$listViewModel = \App\Modules\Base\Models\RelationListView::getInstance($parentRecordModel, $moduleName, $label);
		} else {
			$listViewModel = \App\Modules\Base\Models\ListView::getInstanceForPopup($moduleName, $sourceModule);
		}

		if (!empty($sourceModule)) {
			$listViewModel->set('src_module', $sourceModule);
			$listViewModel->set('src_field', $sourceField);
			$listViewModel->set('src_record', $sourceRecord);
			$listViewModel->set('currency_id', $currencyId);
		}

		if (!empty($orderBy)) {
			$listViewModel->set('orderby', $orderBy);
			$listViewModel->set('sortorder', $sortOrder);
		}
		if ((!empty($searchKey)) && (!empty($searchValue))) {
			$listViewModel->set('search_key', $searchKey);
			$listViewModel->set('search_value', $searchValue);
		}
		if (!empty($relatedParentModule) && !empty($relatedParentId)) {
			$count = $listViewModel->getRelatedEntriesCount();
		} else {
			$count = $listViewModel->getListViewCount();
		}

		return $count;
	}

	/**
	 * Function to get the page count for list
	 * @return total number of pages
	 */
	public function getPageCount(\App\Http\Vtiger_Request $request)
	{
		$listViewCount = $this->getListViewCount($request);
		$pagingModel = new \App\Modules\Base\Models\Paging();
		$pageLimit = $pagingModel->getPageLimit();
		$pageCount = ceil((int) $listViewCount / (int) $pageLimit);

		if ($pageCount == 0) {
			$pageCount = 1;
		}
		$result = [];
		$result['page'] = $pageCount;
		$result['numberOfRecords'] = $listViewCount;
		$response = new \App\Http\Vtiger_Response();
		$response->setResult($result);
		$response->emit();
	}

	protected function showBodyHeader()
	{
		return false;
	}
}
