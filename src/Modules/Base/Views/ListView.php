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

class ListView extends \App\Modules\Base\Views\Index
{

	protected $listViewEntries = null;
	protected $listViewCount = null;
	protected $listViewLinks = null;
	protected $listViewHeaders = null;
	protected $listViewModel;
	protected $viewName;

	public function __construct()
	{
		parent::__construct();
	}

	public function getPageTitle(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$moduleName = $moduleName == 'Vtiger' ? 'YetiForce' : $moduleName;
		$title = \App\Runtime\Vtiger_Language_Handler::translate($moduleName, $moduleName);
		$title = $title . ' - ' . \App\Runtime\Vtiger_Language_Handler::translate('LBL_VIEW_LIST', $moduleName);

		if ($request->has('viewname')) {
			$customView = \App\Modules\CustomView\Models\Record::getAll($moduleName)[$request->get('viewname')];
			if (!empty($customView)) {
				$title .= ' [' . \App\Runtime\Vtiger_Language_Handler::translate('LBL_FILTER', $moduleName) . ': ' . \App\Runtime\Vtiger_Language_Handler::translate($customView->get('viewname'), $moduleName) . ']';
			}
		}
		return $title;
	}

	public function getBreadcrumbTitle(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$title = \App\Runtime\Vtiger_Language_Handler::translate('LBL_VIEW_LIST', $moduleName);
		if ($request->has('viewname')) {
			$customView = \App\Modules\CustomView\Models\Record::getAll($moduleName)[$request->get('viewname')];
			if (!empty($customView)) {
				$title .= '<div class="breadCrumbsFilter dispaly-inline font-small"> [' . \App\Runtime\Vtiger_Language_Handler::translate('LBL_FILTER', $moduleName)
					. ': ' . \App\Runtime\Vtiger_Language_Handler::translate($customView->get('viewname'), $moduleName) . ']</div>';
			}
		}
		return $title;
	}

	public function preProcess(\App\Http\Vtiger_Request $request, $display = true)
	{
		parent::preProcess($request, false);
		// Persist last list display mode per user + module
		if (!$request->isAjax()) {
			$request->getUser()->setPreference('ListViewDefaultView_' . $request->getModule(), 'ListView');
		}
		if ($request->isAjax()) {
			// AJAX requests need list data but not sidebar/layout data
			$this->prepareAjaxListViewData($request);
			return;
		}
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();

		$mid = false;
		if ($request->has('mid')) {
			$mid = $request->get('mid');
		}

		$linkParams = array('MODULE' => $moduleName, 'ACTION' => $request->get('view'));

		$this->viewName = \App\View\CustomView::getInstance($moduleName)->getViewId();
		$this->listViewModel = \App\Modules\Base\Models\ListView::getInstance($moduleName, $this->viewName);
		$this->initializeListViewContents($request, $viewer);

		// ListView needs QUICK_LINKS for sidebar navigation
		$moduleModel = \App\Modules\Base\Models\Module::getInstance($moduleName);
		$linkModels = $moduleModel->getSideBarLinks($linkParams);
		$activeLinkLabel = $this->processSidebarLinks($linkModels, $request);
		$viewer->assign('QUICK_LINKS', $linkModels);
		$viewer->assign('ACTIVE_SIDEBAR_LINK', $activeLinkLabel);

		// Assign all viewer data at the end
		$viewer->assign('CUSTOM_VIEWS', \App\Modules\CustomView\Models\Record::getAllByGroup($moduleName, $mid));
		$viewer->assign('HEADER_LINKS', $this->listViewModel->getHederLinks($linkParams));
		$viewer->assign('VIEWID', $this->viewName);
		$viewer->assign('MODULE_MODEL', $moduleModel);
	}
	
	protected function prepareAjaxListViewData(\App\Http\Vtiger_Request $request)
	{
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		
		// Assign common data needed by AJAX list view
		if (!isset($this->viewName)) {
			// When user changes filter, the new filter id is sent as "viewname" in AJAX requests.
			// If we ignore it, list view will keep using the previous default filter.
			if (!$request->isEmpty('viewname')) {
				$this->viewName = $request->get('viewname');
			} else {
				$this->viewName = \App\View\CustomView::getInstance($moduleName)->getViewId();
			}
		}
		$this->listViewModel = \App\Modules\Base\Models\ListView::getInstance($moduleName, $this->viewName);
		$this->initializeListViewContents($request, $viewer);	
		$viewer->assign('USER_MODEL', $request->getUser());
		$viewer->assign('MODULE_NAME', $moduleName);
		$viewer->assign('MODULE_MODEL', \App\Modules\Base\Models\Module::getInstance($moduleName));
		$viewer->assign('VIEWID', $this->viewName);
	}
	

	public function process(\App\Http\Vtiger_Request $request)
	{
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		
		if ($request->isAjax()) {
			// When filter is changed via AJAX, the new filter is sent as "viewname".
			// Ensure we operate on (and persist) the requested filter, not a stale $this->viewName.
			if (!$request->isEmpty('viewname')) {
				$this->viewName = \App\View\CustomView::getInstance($moduleName)->getViewId(true, $request);
			}
			if (\App\View\CustomView::hasViewChanged($moduleName, $this->viewName, $request)) {
				$customViewModel = \App\Modules\CustomView\Models\Record::getInstanceById($this->viewName);
				if ($customViewModel) {
					\App\View\CustomView::setDefaultSortOrderBy($moduleName, ['orderBy' => $customViewModel->getSortOrderBy('orderBy'), 'sortOrder' => $customViewModel->getSortOrderBy('sortOrder')]);
				}
				\App\View\CustomView::setCurrentView($moduleName, $this->viewName);
			} else {
				\App\View\CustomView::setDefaultSortOrderBy($moduleName);
				if ($request->has('page')) {
					\App\View\CustomView::setCurrentPage($moduleName, $this->viewName, $request->get('page'));
				}
			}

			$this->prepareAjaxListViewData($request);
			$viewer->view('ListViewContents.tpl', $moduleName);
		} else {
			// For non-AJAX requests, just render (data already assigned in preProcess)
			$viewer->view('ListView.tpl', $moduleName);
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
		$moduleName = $request->getModule();

		$jsFileNames = array(
			'modules.Base.resources.Vtiger',
			'modules.Base.resources.ListView',
			"modules.$moduleName.resources.ListView",
			'~libraries/jquery/colorpicker/js/colorpicker.js',
			'modules.CustomView.resources.CustomView',
			"modules.$moduleName.resources.CustomView",
			'modules.Base.resources.CkEditor',
			'modules.Base.resources.ListSearch',
			"modules.$moduleName.resources.ListSearch"
		);

		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		$headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);
		return $headerScriptInstances;
	}

	/**
	 * Retrieves css styles that need to loaded in the page
	 * @param \App\Http\Vtiger_Request $request - request model
	 * @return <array> - array of StyleAsset
	 */
	public function getHeaderCss(\App\Http\Vtiger_Request $request)
	{
		$headerCssInstances = parent::getHeaderCss($request);
		$cssFileNames = array(
			'~libraries/jquery/colorpicker/css/colorpicker.css'
		);
		$cssInstances = $this->checkAndConvertCssStyles($cssFileNames);
		$headerCssInstances = array_merge($headerCssInstances, $cssInstances);
		return $headerCssInstances;
	}
	/*
	 * Function to initialize the required data in smarty to display the List View Contents
	 */

	public function initializeListViewContents(\App\Http\Vtiger_Request $request, \App\Runtime\CRM_Viewer $viewer)
	{
		$moduleName = $request->getModule();
		$pageNumber = $request->get('page');
		$orderBy = $request->get('orderby');
		$sortOrder = $request->get('sortorder');
		$searchResult = $request->get('searchResult');
		if (empty($orderBy) && empty($sortOrder)) {
			$orderBy = \App\View\CustomView::getSortby($moduleName);
			$sortOrder = \App\View\CustomView::getSorder($moduleName);
			if (empty($orderBy)) {
				$moduleInstance = \App\Core\CRMEntity::getInstance($moduleName);
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
			$pageNumber = \App\View\CustomView::getCurrentPage($moduleName, $this->viewName);
		}
		if (!$this->listViewModel) {
			$this->listViewModel = \App\Modules\Base\Models\ListView::getInstance($moduleName, $this->viewName);
		}
		if (!empty($searchResult)) {
			$this->listViewModel->set('searchResult', $searchResult);
		}
		$currentUser = $request->getUser();
		$linkParams = array('MODULE' => $moduleName, 'ACTION' => $request->get('view'), 'CVID' => $this->viewName);
		$linkModels = $this->listViewModel->getListViewMassActions($linkParams);
		$pagingModel = new \App\Modules\Base\Models\Paging();
		$pagingModel->set('page', $pageNumber);
		$pagingModel->set('viewid', $this->viewName);
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
		$viewer->assign('ALPHABET_VALUE', $searchValue);
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
		if (!$this->listViewHeaders) {
			$this->listViewHeaders = $this->listViewModel->getListViewHeaders();
		}
		if (!$this->listViewEntries) {
			$this->listViewEntries = $this->listViewModel->getListViewEntries($pagingModel);
		}
		$noOfEntries = count($this->listViewEntries);
		$viewer->assign('MODULE_MODEL', $this->listViewModel->getModule());
		$viewer->assign('MODULE', $moduleName);
		if (!$this->listViewLinks) {
			$this->listViewLinks = $this->listViewModel->getListViewLinks($linkParams);
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
		if (\App\Core\AppConfig::performance('LISTVIEW_COMPUTE_PAGE_COUNT')) {
			if (!$this->listViewCount) {
				$this->listViewCount = $this->listViewModel->getListViewCount();
			}
			$totalCount = (int) $this->listViewCount;
		} else {
			// Gdy LISTVIEW_COMPUTE_PAGE_COUNT jest wyłączone, oszacuj totalCount na podstawie dostępnych informacji
			// Jeśli nie ma następnej strony, możemy obliczyć dokładną liczbę bez dodatkowego zapytania
			if (!$pagingModel->isNextPageExists()) {
				$totalCount = ($pageNumber - 1) * $pagingModel->getPageLimit() + $noOfEntries;
			} else {
				// Jeśli jest następna strona, musimy obliczyć totalCount dla poprawnej paginacji
				if (!$this->listViewCount) {
					$this->listViewCount = $this->listViewModel->getListViewCount();
				}
				$totalCount = (int) $this->listViewCount;
			}
		}
		// Zawsze ustaw totalCount na pagingModel dla poprawnej paginacji
		if ($totalCount !== false) {
			$pagingModel->set('totalCount', $totalCount);
		}
		$viewer->assign('LISTVIEW_COUNT', $totalCount);
		$viewer->assign('PAGE_COUNT', $pagingModel->getPageCount());
		$viewer->assign('START_PAGIN_FROM', $pagingModel->getStartPagingFrom());
		$viewer->assign('LIST_VIEW_MODEL', $this->listViewModel);
		$viewer->assign('IS_MODULE_EDITABLE', $this->listViewModel->getModule()->isPermitted('EditView'));
		$viewer->assign('IS_MODULE_DELETABLE', $this->listViewModel->getModule()->isPermitted('Delete'));
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
		
		// Prepare data for ListViewContents template - move function calls from templates to controller
		$viewer->assign('AUTO_REFRESH_LIST_ON_CHANGE', \App\Core\AppConfig::performance('AUTO_REFRESH_RECORD_LIST_ON_SELECT_CHANGE'));
		$viewer->assign('LIST_MAX_ENTRIES_MASS_EDIT', \App\Core\AppConfig::main('listMaxEntriesMassEdit'));
	}
}
