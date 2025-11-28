<?php

namespace App\Modules\RecycleBin\Views;

/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * ********************************************************************************** */


class ListView extends \App\Modules\Base\Views\Index
{
	protected $listViewHeaders = [];
	protected $listViewEntries = [];
	protected $listViewCount = 0;

	public function preProcess(\App\Http\Vtiger_Request $request, $display = true)
	{
		parent::preProcess($request, false);
		
		if ($request->isAjax()) {
			// AJAX requests need list data but not sidebar/layout data
			$this->prepareAjaxListViewData($request);
			return;
		}
		
		// Prepare RecycleBin list view data
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();

		$moduleModel = \App\Modules\RecycleBin\Models\Module::getInstance($moduleName);
		$linkParams = array('MODULE' => $moduleName, 'ACTION' => $request->get('view'));
		$quickLinkModels = $moduleModel->getSideBarLinks($linkParams);

		// Process sidebar links to determine active link
		$activeLinkLabel = $this->processSidebarLinks($quickLinkModels, $request);

		// Don't assign MODULE_MODEL here - it will be assigned in initializeListViewContents as sourceModuleModel
		$viewer->assign('QUICK_LINKS', $quickLinkModels);
		$viewer->assign('ACTIVE_SIDEBAR_LINK', $activeLinkLabel);

		// Initialize list view contents
		$this->initializeListViewContents($request, $viewer);
	}
	
	protected function prepareAjaxListViewData(\App\Http\Vtiger_Request $request)
	{
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		
		// Assign common data needed by AJAX list view
		$this->initializeListViewContents($request, $viewer);
		$viewer->assign('USER_MODEL', $request->getUser());
		// MODULE_NAME is already assigned in initializeListViewContents as sourceModule
		$viewer->assign('MODULE', $moduleName);
	}
	
	protected function initializeListViewContents(\App\Http\Vtiger_Request $request, \App\Runtime\CRM_Viewer $viewer)
	{
		$moduleName = $request->getModule();
		$sourceModule = $request->get('sourceModule');

		$pageNumber = $request->get('page');
		$orderBy = $request->get('orderby');
		$sortOrder = $request->get('sortorder');
		if ($sortOrder == "ASC") {
			$nextSortOrder = "DESC";
			$sortImage = "glyphicon glyphicon-chevron-down";
		} else {
			$nextSortOrder = "ASC";
			$sortImage = "glyphicon glyphicon-chevron-up";
		}

		if (empty($pageNumber)) {
			$pageNumber = '1';
		}

		/** @var \App\Modules\RecycleBin\Models\Module $moduleModel */
		$moduleModel = \App\Modules\RecycleBin\Models\Module::getInstance($moduleName);
		//If sourceModule is empty, pick the first module name from the list
		if (empty($sourceModule)) {
			foreach ($moduleModel->getAllModuleList() as $model) {
				$sourceModule = $model->get('name');
				break;
			}
		}
		$listViewModel = \App\Modules\RecycleBin\Models\ListView::getInstance($moduleName, $sourceModule);
		
		// Get source module model for search functionality
		if (empty($sourceModule)) {
			// If sourceModule is still empty, set a default
			$sourceModule = 'Contacts';
		}
		$sourceModuleModel = \App\Modules\Base\Models\Module::getInstance($sourceModule);
		if (!$sourceModuleModel) {
			// Fallback if module doesn't exist
			$sourceModuleModel = \App\Modules\Base\Models\Module::getInstance('Contacts');
			$sourceModule = 'Contacts';
		}

		$linkParams = array('MODULE' => $moduleName, 'ACTION' => $request->get('view'));
		// Add sourceModule to linkParams if available
		if (!empty($sourceModule)) {
			$linkParams['sourceModule'] = $sourceModule;
		}
		$linkModels = $moduleModel->getListViewMassActions($linkParams);

		$pagingModel = new \App\Modules\Base\Models\Paging();
		$pagingModel->set('page', $pageNumber);
		if (empty($orderBy) && empty($sortOrder)) {
			$moduleInstance = \App\Core\CRMEntity::getInstance($moduleName);
			$orderBy = isset($moduleInstance->default_order_by) ? $moduleInstance->default_order_by : 'modifiedtime';
			$sortOrder = isset($moduleInstance->default_sort_order) ? $moduleInstance->default_sort_order : 'DESC';
		}
		if (!empty($orderBy)) {
			$listViewModel->set('orderby', $orderBy);
			$listViewModel->set('sortorder', $sortOrder);
		}
		
		// Handle search parameters
		$searchKey = $request->get('search_key');
		$searchValue = $request->get('search_value');
		$operator = $request->get('operator');
		if (!empty($operator)) {
			$listViewModel->set('operator', $operator);
		}
		$viewer->assign('OPERATOR', $operator);
		$viewer->assign('ALPHABET_VALUE', $searchValue);
		if (!empty($searchKey) && !empty($searchValue)) {
			$listViewModel->set('search_key', $searchKey);
			$listViewModel->set('search_value', $searchValue);
		}
		$searchParams = $request->get('search_params');
		if (!empty($searchParams) && is_array($searchParams)) {
			$transformedSearchParams = $listViewModel->get('query_generator')->parseBaseSearchParamsToCondition($searchParams);
			$listViewModel->set('search_params', $transformedSearchParams);
			//To make smarty to get the details easily accesible
			foreach ($searchParams as $fieldListGroup) {
				foreach ($fieldListGroup as $fieldSearchInfo) {
					$fieldSearchInfo['searchValue'] = isset($fieldSearchInfo[2]) ? $fieldSearchInfo[2] : '';
					$fieldSearchInfo['fieldName'] = $fieldName = isset($fieldSearchInfo[0]) ? $fieldSearchInfo[0] : '';
					$fieldSearchInfo['specialOption'] = isset($fieldSearchInfo[3]) ? $fieldSearchInfo[3] : '';
					$searchParams[$fieldName] = $fieldSearchInfo;
				}
			}
		} else {
			$searchParams = [];
		}

		if (empty($this->listViewHeaders)) {
			$this->listViewHeaders = $listViewModel->getListViewHeaders();
		}
		if (empty($this->listViewEntries)) {
			$this->listViewEntries = $listViewModel->getListViewEntries($pagingModel);
		}
		$noOfEntries = is_array($this->listViewEntries) ? count($this->listViewEntries) : 0;

		$viewer->assign('MODULE', $moduleName);

		// Initialize HEADER_LINKS to prevent template errors
		$headerLinks = ['LIST_VIEW_HEADER' => []];
		$viewer->assign('HEADER_LINKS', $headerLinks);

		// Get list view links (including "Back to Source Module" button if sourceModule is available)
		$listViewLinks = $moduleModel->getListViewLinks($linkParams);
		$viewer->assign('LISTVIEW_LINKS', $listViewLinks);
		$viewer->assign('LISTVIEW_MASSACTIONS', $linkModels);

		$viewer->assign('PAGING_MODEL', $pagingModel);
		$viewer->assign('PAGE_NUMBER', $pageNumber);

		$viewer->assign('ORDER_BY', $orderBy);
		$viewer->assign('SORT_ORDER', $sortOrder);
		$viewer->assign('NEXT_SORT_ORDER', $nextSortOrder);
		$viewer->assign('SORT_IMAGE', $sortImage);
		$viewer->assign('COLUMN_NAME', $orderBy);

		$viewer->assign('LISTVIEW_ENTRIES_COUNT', $noOfEntries);
		$viewer->assign('LISTVIEW_HEADERS', $this->listViewHeaders);
		$viewer->assign('LISTVIEW_ENTRIES', $this->listViewEntries);
		$viewer->assign('MODULE_LIST', $moduleModel->getAllModuleList());
		$viewer->assign('SOURCE_MODULE', $sourceModule);
		$viewer->assign('DELETED_RECORDS_TOTAL_COUNT', $moduleModel->getDeletedRecordsTotalCount());
		
		// Assign source module model for search functionality
		// Also assign as MODULE_MODEL for template compatibility
		$viewer->assign('SOURCE_MODULE_MODEL', $sourceModuleModel);
		$viewer->assign('MODULE_MODEL', $sourceModuleModel);
		$viewer->assign('MODULE_NAME', $sourceModule);

		if (!$this->listViewCount) {
			$this->listViewCount = $listViewModel->getListViewCount();
		}
		$pagingModel->set('totalCount', (int) $this->listViewCount);
		$viewer->assign('LISTVIEW_COUNT', $this->listViewCount);

		$pageCount = $pagingModel->getPageCount();
		$startPaginFrom = $pagingModel->getStartPagingFrom();

		$viewer->assign('PAGE_COUNT', $pageCount);
		$viewer->assign('START_PAGIN_FROM', $startPaginFrom);
		$viewer->assign('IS_MODULE_DELETABLE', $listViewModel->getModule()->isPermitted('Delete'));
		$viewer->assign('LIST_MAX_ENTRIES_MASS_EDIT', \App\Core\AppConfig::main('listMaxEntriesMassEdit'));
		
		// Ensure search details exist for all headers to avoid undefined index notices in templates
		if (is_array($this->listViewHeaders)) {
			foreach ($this->listViewHeaders as $header) {
				$headerName = $header->getName();
				if (!isset($searchParams[$headerName])) {
					$searchParams[$headerName] = ['searchValue' => '', 'fieldName' => $headerName];
				}
			}
		}
		$viewer->assign('SEARCH_DETAILS', $searchParams);
		
		// Assign USER_MODEL (always assign, overwrites if already set)
		$viewer->assign('USER_MODEL', $request->getUser());
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		
		if ($request->isAjax()) {
			$this->prepareAjaxListViewData($request);
			$viewer->view('ListViewContents.tpl', $moduleName);
		} else {
			// For non-AJAX requests, just render (data already assigned in preProcess)
			$viewer->view('ListView.tpl', $moduleName);
		}
	}

	/**
	 * Function to get breadcrumb title for the current page
	 * @param \App\Http\Vtiger_Request $request
	 * @return string
	 */
	public function getBreadcrumbTitle(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$sourceModule = $request->get('sourceModule');
		$title = \App\Runtime\Vtiger_Language_Handler::translate('LBL_VIEW_LIST', $moduleName);
		
		// Add source module name to breadcrumb if available
		if (!empty($sourceModule)) {
			$sourceModuleLabel = \App\Runtime\Vtiger_Language_Handler::translate($sourceModule, $sourceModule);
			$title .= ' - ' . $sourceModuleLabel;
		}
		
		return $title;
	}

	/**
	 * Function to get the list of Script models to be included
	 * @param \App\Http\Vtiger_Request $request
	 * @return <Array> - List of \App\Modules\Base\Models\JsScript instances
	 */
	public function getFooterScripts(\App\Http\Vtiger_Request $request)
	{
		$headerScriptInstances = parent::getFooterScripts($request);
		$moduleName = $request->getModule();
		$jsFileNames = array(
			'modules.Base.resources.ListView',
			"modules.$moduleName.resources.ListView",
			'modules.CustomView.resources.CustomView',
			"modules.$moduleName.resources.CustomView",
			'modules.Base.resources.CkEditor',
			'modules.Base.resources.ListSearch'
		);
		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		$headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);
		return $headerScriptInstances;
	}

	/**
	 * Function to get the page count for list
	 */
	public function getPageCount(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$sourceModule = $request->get('sourceModule');
		$listViewModel = \App\Modules\RecycleBin\Models\ListView::getInstance($moduleName, $sourceModule);
		
		// Handle search parameters for page count
		$searchKey = $request->get('search_key');
		$searchValue = $request->get('search_value');
		$operator = $request->get('operator');
		if (!empty($operator)) {
			$listViewModel->set('operator', $operator);
		}
		if (!empty($searchKey) && !empty($searchValue)) {
			$listViewModel->set('search_key', $searchKey);
			$listViewModel->set('search_value', $searchValue);
		}
		$searchParams = $request->get('search_params');
		if (!empty($searchParams) && is_array($searchParams)) {
			$transformedSearchParams = $listViewModel->get('query_generator')->parseBaseSearchParamsToCondition($searchParams);
			$listViewModel->set('search_params', $transformedSearchParams);
		}

		$listViewCount = $listViewModel->getListViewCount();
		$pagingModel = new \App\Modules\Base\Models\Paging();
		$pageLimit = $pagingModel->getPageLimit();
		$pageCount = ceil((int) $listViewCount / (int) $pageLimit);

		if ($pageCount == 0) {
			$pageCount = 1;
		}
		$result = array();
		$result['page'] = $pageCount;
		$result['numberOfRecords'] = $listViewCount;
		$response = new \App\Http\Vtiger_Response();
		$response->setResult($result);
		$response->emit();
	}

	/**
	 * Function returns the number of records for the current filter
	 * @param \App\Http\Vtiger_Request $request
	 */
	public function getRecordsCount(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$sourceModule = $request->get('sourceModule');
		$listViewModel = \App\Modules\RecycleBin\Models\ListView::getInstance($moduleName, $sourceModule);
		
		// Handle search parameters for records count
		$searchKey = $request->get('search_key');
		$searchValue = $request->get('search_value');
		$operator = $request->get('operator');
		if (!empty($operator)) {
			$listViewModel->set('operator', $operator);
		}
		if (!empty($searchKey) && !empty($searchValue)) {
			$listViewModel->set('search_key', $searchKey);
			$listViewModel->set('search_value', $searchValue);
		}
		$searchParams = $request->get('search_params');
		if (!empty($searchParams) && is_array($searchParams)) {
			$transformedSearchParams = $listViewModel->get('query_generator')->parseBaseSearchParamsToCondition($searchParams);
			$listViewModel->set('search_params', $transformedSearchParams);
		}

		$count = $listViewModel->getListViewCount();

		$result = array();
		$result['module'] = $moduleName;
		$result['count'] = $count;

		$response = new \App\Http\Vtiger_Response();
		$response->setEmitType(\App\Http\Vtiger_Response::$EMIT_JSON);
		$response->setResult($result);
		$response->emit();
	}
}
