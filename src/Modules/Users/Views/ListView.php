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


class ListView extends \App\Modules\Settings\Base\Views\ListView
{

	public function __construct()
	{
		parent::__construct();
		$this->exposeMethod('getListViewCount');
		$this->exposeMethod('getRecordsCount');
		$this->exposeMethod('getPageCount');
	}

	/**
	 * Initialize listViewModel with Users-specific model and status filtering
	 */
	protected function initializeListViewModel(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$cvId = $request->get('viewname');
		
		// Initialize listViewModel with Users/Models/ListView if not set
		if (!$this->listViewModel) {
			$this->listViewModel = \App\Modules\Users\Models\ListView::getInstance($moduleName, $cvId);
			
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
		$moduleName = $request->getModule();
		
		// Add Users-specific scripts (parent already includes Settings scripts)
		$jsFileNames = [
			'modules.Users.resources.List',
		];
		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		$headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);
		return $headerScriptInstances;
	}

	public function preProcess(\App\Http\Vtiger_Request $request, $display = true)
	{
		// Initialize Users-specific listViewModel BEFORE parent::preProcess()
		$this->initializeListViewModel($request);
		
		// Call parent to handle all common ListView logic
		parent::preProcess($request, false);
		
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		$cvId = $request->get('viewname');
		$listViewModel = $this->listViewModel;
		
		// Users-specific overrides after parent::preProcess()
		
		// Override pagingModel with cvId if provided
		$pagingModel = $viewer->getTemplateVars('PAGING_MODEL');
		if ($pagingModel && !empty($cvId)) {
			$pagingModel->set('viewid', $cvId);
		}
		
		// Override links with cvId in linkParams
		$linkParams = array('MODULE' => $moduleName, 'ACTION' => $request->get('view'), 'CVID' => $cvId);
		if (!isset($this->listViewLinks)) {
			$this->listViewLinks = $listViewModel->getListViewLinks($linkParams);
		}
		
		// Ensure LISTVIEW_LINKS structure (required by template)
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
		
		// Override LISTVIEW_MASSACTIONS with cvId-aware version (parent doesn't assign this)
		$linkModels = $listViewModel->getListViewMassActions($linkParams);
		// Ensure LISTVIEW_MASSACTIONS is always an array (required by template)
		// Template uses count($LISTVIEW_MASSACTIONS), so it must be an array, never null
		if (!is_array($linkModels) || !isset($linkModels['LISTVIEWMASSACTION']) || !is_array($linkModels['LISTVIEWMASSACTION'])) {
			$listViewMassActions = [];
		} else {
			$listViewMassActions = $linkModels['LISTVIEWMASSACTION'];
		}
		$viewer->assign('LISTVIEW_MASSACTIONS', $listViewMassActions);
		
		// Override HEADER_LINKS with cvId
		$viewer->assign('HEADER_LINKS', $listViewModel->getHederLinks($linkParams));
		if (!empty($cvId)) {
			$viewer->assign('VIEWID', $cvId);
		}
		
		// Users-specific assignments
		$viewer->assign('USER_MODEL', $request->getUser());
		$viewer->assign('LIST_MAX_ENTRIES_MASS_EDIT', \App\AppConfig::main('listMaxEntriesMassEdit'));
		$viewer->assign('CUSTOM_VIEWS', []);
		
		// Handle search_params (Users uses 'search_params' instead of parent's 'searchParams')
		$searchParams = $request->get('search_params');
		$searchResult = $request->get('searchResult');
		if (!empty($searchParams) && is_array($searchParams)) {
			$transformedSearchParams = $listViewModel->get('query_generator')->parseBaseSearchParamsToCondition($searchParams);
			$listViewModel->set('search_params', $transformedSearchParams);
			// Transform for template access
			foreach ($searchParams as $fieldListGroup) {
				foreach ($fieldListGroup as $fieldSearchInfo) {
					$fieldSearchInfo['searchValue'] = isset($fieldSearchInfo[2]) ? $fieldSearchInfo[2] : '';
					$fieldSearchInfo['fieldName'] = $fieldName = isset($fieldSearchInfo[0]) ? $fieldSearchInfo[0] : '';
					$fieldSearchInfo['specialOption'] = isset($fieldSearchInfo[3]) ? $fieldSearchInfo[3] : '';
					$searchParams[$fieldName] = $fieldSearchInfo;
				}
			}
			$viewer->assign('SEARCH_DETAILS', $searchParams);
		}
		
		if (!empty($searchResult) && is_array($searchResult)) {
			$listViewModel->get('query_generator')->addNativeCondition(['vtiger_crmentity.crmid' => $searchResult]);
		}
		
		// Handle ALPHABET_VALUE and OPERATOR (parent doesn't assign these)
		$searchKey = $request->get('search_key');
		$searchValue = $request->get('search_value');
		$operator = $request->get('operator');
		$viewer->assign('OPERATOR', $operator);
		if ('status' == $searchKey) {
			$viewer->assign('ALPHABET_VALUE', '');
		} else {
			$viewer->assign('ALPHABET_VALUE', $searchValue);
		}
	}


	public function process(\App\Http\Vtiger_Request $request)
	{
		// Handle AJAX endpoints (getListViewCount, getRecordsCount, getPageCount)
		if ($request->isAjax()) {
			$mode = $request->get('mode');
			if (!empty($mode)) {
				$this->invokeExposedMethod($mode, $request);
				return;
			}
		}
		
		// For all other requests (AJAX ListViewContent and non-AJAX), use parent
		parent::process($request);
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
		$cvId = $request->get('viewname');
		$listViewModel = \App\Modules\Users\Models\ListView::getInstance($moduleName, $cvId);
		
		// Status filtering - specjalne dla Users
		$status = $request->get('status');
		if (empty($status)) {
			$status = 'Active';
		}
		$listViewModel->set('status', $status);

		// Apply search filters
		$searchKey = $request->get('search_key');
		$searchValue = $request->get('search_value');
		if (!empty($searchKey) && !empty($searchValue)) {
			$listViewModel->set('search_key', $searchKey);
			$listViewModel->set('search_value', $searchValue);
		}
		
		$operator = $request->get('operator');
		if (!empty($operator)) {
			$listViewModel->set('operator', $operator);
		}
		
		$searchParams = $request->get('search_params');
		if (!empty($searchParams) && is_array($searchParams)) {
			$transformedSearchParams = $listViewModel->get('query_generator')->parseBaseSearchParamsToCondition($searchParams);
			$listViewModel->set('search_params', $transformedSearchParams);
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
