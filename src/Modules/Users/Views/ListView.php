<?php

namespace App\Modules\Users\Views;

/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * ********************************************************************************** */


class ListView extends \App\Modules\Base\Views\ListView
{

	public function __construct()
	{
		parent::__construct();
		$this->exposeMethod('getListViewCount');
		$this->exposeMethod('getRecordsCount');
		$this->exposeMethod('getPageCount');
	}

	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		$currentUserModel = $request->getUser();
		if (!$currentUserModel->isAdminUser()) {
			throw new \App\Exceptions\NoPermitted('LBL_PERMISSION_DENIED');
		}
	}

	public function getFooterScripts(\App\Http\Vtiger_Request $request)
	{
		$headerScriptInstances = parent::getFooterScripts($request);
		$jsFileNames = [
			'modules.Base.resources.ListView',
			'modules.Users.resources.List',
		];
		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		$headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);
		return $headerScriptInstances;
	}

	public function preProcess(\App\Http\Vtiger_Request $request, $display = true)
	{
		parent::preProcess($request, false);
		
		if ($request->isAjax()) {
			// AJAX requests - przygotuj dane dla ListViewContents
			$this->prepareAjaxListViewData($request);
			return;
		}
		
		// Non-AJAX requests - pełne przygotowanie danych
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		
		// Inicjalizuj listViewModel z Users/Models/ListView
		$cvId = $request->get('viewname');
		if (empty($cvId)) {
			$cvId = \App\CustomView::getInstance($moduleName)->getViewId();
		}
		$this->viewName = $cvId;
		$this->listViewModel = \App\Modules\Users\Models\ListView::getInstance($moduleName, $cvId);
		
		// Status filtering - specjalne dla Users
		$status = $request->get('status');
		if (empty($status)) {
			$status = 'Active';
		}
		$this->listViewModel->set('status', $status);
		
		// Inicjalizuj zawartość listy
		$this->initializeListViewContents($request, $viewer);
		
		// Dodatkowe przypisania specyficzne dla Users
		$linkParams = array('MODULE' => $moduleName, 'ACTION' => $request->get('view'), 'CVID' => $cvId);
		$viewer->assign('HEADER_LINKS', $this->listViewModel->getHederLinks($linkParams));
		$viewer->assign('VIEWID', $this->viewName);
		$viewer->assign('MODULE_MODEL', $this->listViewModel->getModule());
		
		// CUSTOM_VIEWS - Users może nie mieć custom views, ale Base\Views\ListView tego oczekuje
		// Sprawdź czy Users ma custom views, jeśli nie - przypisz pustą tablicę
		try {
			$customViews = \App\Modules\CustomView\Models\Record::getAllByGroup($moduleName);
			$viewer->assign('CUSTOM_VIEWS', $customViews);
		} catch (\Exception $e) {
			$viewer->assign('CUSTOM_VIEWS', []);
		}
	}

	protected function prepareAjaxListViewData(\App\Http\Vtiger_Request $request)
	{
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		
		// Inicjalizuj listViewModel jeśli jeszcze nie został
		if (!isset($this->viewName)) {
			$cvId = $request->get('viewname');
			if (empty($cvId)) {
				$cvId = \App\CustomView::getInstance($moduleName)->getViewId();
			}
			$this->viewName = $cvId;
		}
		
		if (!$this->listViewModel) {
			$this->listViewModel = \App\Modules\Users\Models\ListView::getInstance($moduleName, $this->viewName);
			
			// Status filtering - specjalne dla Users
			$status = $request->get('status');
			if (empty($status)) {
				$status = 'Active';
			}
			$this->listViewModel->set('status', $status);
		}
		
		// Użyj initializeListViewContents() z nadpisaniem dla Users
		$this->initializeListViewContents($request, $viewer);
		
		// Dodatkowe przypisania dla AJAX
		$viewer->assign('USER_MODEL', $request->getUser());
		$viewer->assign('MODULE_NAME', $moduleName);
		$viewer->assign('MODULE_MODEL', $this->listViewModel->getModule());
		$viewer->assign('VIEWID', $this->viewName);
	}

	public function initializeListViewContents(\App\Http\Vtiger_Request $request, \App\Runtime\CRM_Viewer $viewer)
	{
		$moduleName = $request->getModule();
		$pageNumber = $request->get('page');
		$orderBy = $request->get('orderby');
		$sortOrder = $request->get('sortorder');
		$searchResult = $request->get('searchResult');
		
		// Użyj Users/Models/ListView jeśli nie został jeszcze zainicjalizowany
		if (!$this->listViewModel) {
			$cvId = $request->get('viewname');
			if (empty($cvId)) {
				$cvId = \App\CustomView::getInstance($moduleName)->getViewId();
			}
			$this->viewName = $cvId;
			$this->listViewModel = \App\Modules\Users\Models\ListView::getInstance($moduleName, $cvId);
		}
		
		// Status filtering - specjalne dla Users
		$status = $request->get('status');
		if (empty($status)) {
			$status = 'Active';
		}
		$this->listViewModel->set('status', $status);
		
		if (empty($orderBy) && empty($sortOrder)) {
			$orderBy = \App\CustomView::getSortby($moduleName);
			$sortOrder = \App\CustomView::getSorder($moduleName);
			if (empty($orderBy)) {
				$moduleInstance = \App\CRMEntity::getInstance($moduleName);
				$orderBy = $moduleInstance->default_order_by;
				$sortOrder = $moduleInstance->default_sort_order;
			}
		}
		
		if ($sortOrder === 'ASC') {
			$nextSortOrder = 'DESC';
			$sortImage = 'glyphicon glyphicon-chevron-down';
		} else {
			$nextSortOrder = 'ASC';
			$sortImage = 'glyphicon glyphicon-chevron-up';
		}
		
		if (empty($pageNumber)) {
			$pageNumber = \App\CustomView::getCurrentPage($moduleName, $this->viewName);
			if (empty($pageNumber)) {
				$pageNumber = '1';
			}
		}
		
		if (!empty($searchResult) && is_array($searchResult)) {
			$this->listViewModel->set('searchResult', $searchResult);
		}
		
		$currentUser = $request->getUser();
		$cvId = $this->viewName ?? \App\CustomView::getInstance($moduleName)->getViewId();
		$linkParams = array('MODULE' => $moduleName, 'ACTION' => $request->get('view'), 'CVID' => $cvId);
		$linkModels = $this->listViewModel->getListViewMassActions($linkParams);
		
		// Ensure LISTVIEWMASSACTION is always an array
		if (!isset($linkModels['LISTVIEWMASSACTION'])) {
			$linkModels['LISTVIEWMASSACTION'] = [];
		}
		
		$pagingModel = new \App\Modules\Base\Models\Paging();
		$pagingModel->set('page', $pageNumber);
		$pagingModel->set('viewid', $cvId);
		
		if (!empty($orderBy)) {
			$this->listViewModel->set('orderby', $orderBy);
			$this->listViewModel->set('sortorder', $sortOrder);
		}
		
		$searchKey = $request->get('search_key');
		$searchValue = $request->get('search_value');
		$operator = $request->get('operator');
		if (!empty($operator)) {
			$this->listViewModel->set('operator', $operator);
		}
		$viewer->assign('OPERATOR', $operator);
		
		// Specjalna logika dla Users - ALPHABET_VALUE tylko jeśli searchKey != 'status'
		if ('status' != $searchKey) {
			$viewer->assign('ALPHABET_VALUE', $searchValue);
		}
		
		if (!empty($searchKey) && !empty($searchValue)) {
			$this->listViewModel->set('search_key', $searchKey);
			$this->listViewModel->set('search_value', $searchValue);
		}
		
		$searchParams = $request->get('search_params');
		if (!empty($searchParams) && is_array($searchParams)) {
			$transformedSearchParams = $this->listViewModel->get('query_generator')->parseBaseSearchParamsToCondition($searchParams);
			$this->listViewModel->set('search_params', $transformedSearchParams);
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
		
		if (!empty($searchResult) && is_array($searchResult)) {
			$this->listViewModel->get('query_generator')->addNativeCondition(['vtiger_crmentity.crmid' => $searchResult]);
		}
		
		if (!$this->listViewHeaders) {
			$this->listViewHeaders = $this->listViewModel->getListViewHeaders();
		}
		if (!$this->listViewEntries) {
			$this->listViewEntries = $this->listViewModel->getListViewEntries($pagingModel);
		}
		$noOfEntries = count($this->listViewEntries);
		
		$viewer->assign('MODULE_MODEL', $this->listViewModel->getModule());
		$viewer->assign('MODULE', $moduleName);
		
		if (!isset($this->listViewLinks)) {
			$this->listViewLinks = $this->listViewModel->getListViewLinks($linkParams);
		}
		
		// Ensure LISTVIEW_LINKS is always an array with required keys (specjalne dla Users)
		if (!is_array($this->listViewLinks)) {
			$this->listViewLinks = [];
		}
		if (!isset($this->listViewLinks['LISTVIEW'])) {
			$this->listViewLinks['LISTVIEW'] = [];
		}
		if (!isset($this->listViewLinks['LISTVIEWBASIC'])) {
			$this->listViewLinks['LISTVIEWBASIC'] = [];
		}
		
		$viewer->assign('LISTVIEW_LINKS', $this->listViewLinks);
		$viewer->assign('LISTVIEW_MASSACTIONS', $linkModels['LISTVIEWMASSACTION']);
		$viewer->assign('PAGING_MODEL', $pagingModel);
		$viewer->assign('PAGE_NUMBER', $pageNumber);
		$viewer->assign('ORDER_BY', $orderBy);
		$viewer->assign('SORT_ORDER', $sortOrder);
		$viewer->assign('NEXT_SORT_ORDER', $nextSortOrder);
		$viewer->assign('SORT_IMAGE', $sortImage);
		$viewer->assign('COLUMN_NAME', $orderBy);
		$viewer->assign('LISTVIEW_HEADERS', $this->listViewHeaders);
		$viewer->assign('LISTVIEW_ENTRIES', $this->listViewEntries);
		$viewer->assign('LISTVIEW_ENTRIES_COUNT', $noOfEntries);
		
		$totalCount = false;
		if (\App\AppConfig::performance('LISTVIEW_COMPUTE_PAGE_COUNT')) {
			if (!$this->listViewCount) {
				$this->listViewCount = $this->listViewModel->getListViewCount();
			}
			$pagingModel->set('totalCount', (int) $this->listViewCount);
			$totalCount = (int) $this->listViewCount;
		}
		$viewer->assign('LISTVIEW_COUNT', $totalCount);
		$viewer->assign('PAGE_COUNT', $pagingModel->getPageCount());
		$viewer->assign('START_PAGIN_FROM', $pagingModel->getStartPagingFrom());
		$viewer->assign('LIST_VIEW_MODEL', $this->listViewModel);
		$viewer->assign('IS_MODULE_EDITABLE', $this->listViewModel->getModule()->isPermitted('EditView'));
		$viewer->assign('IS_MODULE_DELETABLE', $this->listViewModel->getModule()->isPermitted('Delete'));
		$viewer->assign('SEARCH_DETAILS', $searchParams);
		
		// Users-specific assignments
		$viewer->assign('QUALIFIED_MODULE', $moduleName);
		$viewer->assign('USER_MODEL', $request->getUser());
		
		// Dodaj LIST_MAX_ENTRIES_MASS_EDIT dla refaktoryzacji vglobal
		$viewer->assign('LIST_MAX_ENTRIES_MASS_EDIT', \App\AppConfig::main('listMaxEntriesMassEdit'));
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		
		if ($request->isAjax()) {
			// AJAX requests - sprawdź czy to endpoint (mode) czy renderowanie ListViewContents
			$mode = $request->get('mode');
			if (!empty($mode)) {
				// To jest endpoint AJAX (getListViewCount, getRecordsCount, getPageCount)
				$this->invokeExposedMethod($mode, $request);
				return;
			}
			
			// To jest renderowanie ListViewContents przez AJAX
			// Dane już przygotowane w preProcess()->prepareAjaxListViewData()
			$viewer->view('ListViewContents.tpl', $moduleName);
		} else {
			// Non-AJAX requests - pełna strona
			// Dane już przygotowane w preProcess()
			$viewer->view('ListView.tpl', $request->getModule(false));
		}
	}

	/**
	 * Function returns the number of records for the current filter
	 * @param \App\Http\Vtiger_Request $request
	 */
	public function getRecordsCount(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$cvId = $request->get('viewname');
		$count = $this->getListViewCount($request);

		$result = array();
		$result['module'] = $moduleName;
		$result['viewname'] = $cvId;
		$result['count'] = $count;

		$response = new \App\Http\Vtiger_Response();
		$response->setEmitType(\App\Http\Vtiger_Response::$EMIT_JSON);
		$response->setResult($result);
		$response->emit();
	}

	/**
	 * Function to get listView count
	 * @param \App\Http\Vtiger_Request $request
	 */
	public function getListViewCount(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$cvId = \App\CustomView::getInstance($moduleName)->getViewId();
		if (empty($cvId)) {
			$cvId = '0';
		}

		$searchKey = $request->get('search_key');
		$searchValue = $request->get('search_value');
		$searchParmams = $request->get('search_params');
		$operator = $request->get('operator');
		$listViewModel = \App\Modules\Users\Models\ListView::getInstance($moduleName, $cvId);

		if (empty($searchParmams) || !is_array($searchParmams)) {
			$searchParmams = [];
		}
		$transformedSearchParams = $listViewModel->get('query_generator')->parseBaseSearchParamsToCondition($searchParmams);
		$listViewModel->set('search_params', $transformedSearchParams);
		if (!empty($operator)) {
			$listViewModel->set('operator', $operator);
		}
		if (!empty($searchKey) && !empty($searchValue)) {
			$listViewModel->set('search_key', $searchKey);
			$listViewModel->set('search_value', $searchValue);
		}

		return $listViewModel->getListViewCount();
	}

	/**
	 * Function to get the page count for list
	 * @return 
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
		$result = array();
		$result['page'] = $pageCount;
		$result['numberOfRecords'] = $listViewCount;
		$response = new \App\Http\Vtiger_Response();
		$response->setResult($result);
		$response->emit();
	}
}
