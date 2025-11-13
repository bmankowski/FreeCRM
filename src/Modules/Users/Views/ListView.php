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

	/**
	 * Initialize listViewModel with Users-specific model and status filtering
	 * This hook method is called before initializeListViewContents()
	 */
	protected function initializeListViewModel(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		
		// Initialize viewName if not set
		if (!isset($this->viewName)) {
			$cvId = $request->get('viewname');
			if (empty($cvId)) {
				$cvId = \App\CustomView::getInstance($moduleName)->getViewId();
			}
			$this->viewName = $cvId;
		}
		
		// Initialize listViewModel with Users/Models/ListView if not set
		if (!$this->listViewModel) {
			$this->listViewModel = \App\Modules\Users\Models\ListView::getInstance($moduleName, $this->viewName);
			
			// Status filtering - specjalne dla Users
			$status = $request->get('status');
			if (empty($status)) {
				$status = 'Active';
			}
			$this->listViewModel->set('status', $status);
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
		// Initialize Users-specific listViewModel before parent::preProcess()
		// This ensures parent::preProcess() will use our listViewModel when calling initializeListViewContents()
		$this->initializeListViewModel($request);
		
		// Call parent::preProcess() which will call our overridden initializeListViewContents()
		parent::preProcess($request, false);
		
		if ($request->isAjax()) {
			// AJAX requests - prepareAjaxListViewData() already called in parent::preProcess()
			return;
		}
		
		// Non-AJAX requests - override assignments with Users-specific values
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		$cvId = $this->viewName;
		
		// Override assignments with Users-specific model
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
		// Initialize Users-specific listViewModel before parent's initializeListViewContents()
		$this->initializeListViewModel($request);
		
		// Call parent to get common AJAX data and initializeListViewContents()
		parent::prepareAjaxListViewData($request);
		
		// Override MODULE_MODEL with Users-specific model
		$viewer = $this->getViewer($request);
		$viewer->assign('MODULE_MODEL', $this->listViewModel->getModule());
	}

	public function initializeListViewContents(\App\Http\Vtiger_Request $request, \App\Runtime\CRM_Viewer $viewer)
	{
		// Ensure Users-specific listViewModel is initialized
		$this->initializeListViewModel($request);
		
		// Call parent to handle all common ListView initialization
		parent::initializeListViewContents($request, $viewer);
		
		// Users-specific customizations
		$moduleName = $request->getModule();
		$searchKey = $request->get('search_key');
		$searchValue = $request->get('search_value');
		
		// Specjalna logika dla Users - ALPHABET_VALUE tylko jeśli searchKey != 'status'
		// Parent przypisuje ALPHABET_VALUE bezwarunkowo, więc nadpisujemy jeśli searchKey == 'status'
		if ('status' == $searchKey) {
			$viewer->assign('ALPHABET_VALUE', '');
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
		
		// Ensure LISTVIEWMASSACTION is always an array
		$linkParams = array('MODULE' => $moduleName, 'ACTION' => $request->get('view'), 'CVID' => $this->viewName);
		$linkModels = $this->listViewModel->getListViewMassActions($linkParams);
		if (!isset($linkModels['LISTVIEWMASSACTION'])) {
			$linkModels['LISTVIEWMASSACTION'] = [];
		}
		$viewer->assign('LISTVIEW_MASSACTIONS', $linkModels['LISTVIEWMASSACTION']);
		
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
			// Handle AJAX endpoints (getListViewCount, getRecordsCount, getPageCount)
			$mode = $request->get('mode');
			if (!empty($mode)) {
				// To jest endpoint AJAX - wywołaj metodę i zakończ
				$this->invokeExposedMethod($mode, $request);
				return;
			}
			
			// Handle CustomView state management (from parent::process())
			if (\App\CustomView::hasViewChanged($moduleName, $this->viewName, $request)) {
				$customViewModel = \App\Modules\CustomView\Models\Record::getInstanceById($this->viewName);
				if ($customViewModel) {
					\App\CustomView::setDefaultSortOrderBy($moduleName, ['orderBy' => $customViewModel->getSortOrderBy('orderBy'), 'sortOrder' => $customViewModel->getSortOrderBy('sortOrder')]);
				}
				\App\CustomView::setCurrentView($moduleName, $this->viewName);
			} else {
				\App\CustomView::setDefaultSortOrderBy($moduleName);
				if ($request->has('page')) {
					\App\CustomView::setCurrentPage($moduleName, $this->viewName, $request->get('page'));
				}
			}
			
			// prepareAjaxListViewData() already called in preProcess(), but parent::process() calls it again
			// Our overridden prepareAjaxListViewData() will be called, which is fine
			$this->prepareAjaxListViewData($request);
			$viewer->view('ListViewContents.tpl', $moduleName);
		} else {
			// For non-AJAX requests, data already assigned in preProcess()
			// Use getModule(false) for Users-specific template path
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
