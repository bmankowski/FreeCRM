<?php

namespace App\Modules\Settings\Base\Views;


/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * ********************************************************************************** */


class ListView extends \App\Modules\Settings\Base\Views\Index
{

	protected $listViewEntries = null;
	protected $listViewHeaders = null;
	/** @var \App\Modules\Settings\Base\Models\ListView|null */
	protected $listViewModel = null;
	protected $listViewLinks;
	protected $listViewCount;

	public function __construct()
	{
		parent::__construct();
	}

	public function preProcess(\App\Http\Vtiger_Request $request, $display = true)
	{
		parent::preProcess($request, false);
		$this->prepareListViewData($request);
	}

	public function preProcessAjax(\App\Http\Vtiger_Request $request)
	{
		$this->prepareListViewData($request);
	}

	/**
	 * Prepare data for ListViewHeader templates
	 * Modules can override this to prepare module-specific data
	 * Moves function calls from templates to controller for better MVC separation
	 */
	protected function prepareListViewHeaderData($viewer, $qualifiedModuleName)
	{
		// Prepare AdvancedPermission-specific data
		if ($qualifiedModuleName === 'Settings:AdvancedPermission') {
			$viewer->assign('PERMITTED_BY_ADVANCED_PERMISSION', \App\AppConfig::security('PERMITTED_BY_ADVANCED_PERMISSION'));
			$viewer->assign('CACHING_PERMISSION_TO_RECORD', \App\AppConfig::security('CACHING_PERMISSION_TO_RECORD'));
		}
	}

	/**
	 * Resolve ListView model instance for the current request.
	 * Allows subclasses to provide module-specific implementations.
	 */
	protected function createListViewModel(\App\Http\Vtiger_Request $request)
	{
		return \App\Modules\Settings\Base\Models\ListView::getInstance($request->getModule(false));
	}

	protected function prepareListViewData(\App\Http\Vtiger_Request $request)
	{
		$viewer = $this->getViewer($request);
		$qualifiedModuleName = $request->getModule(false);
		$pageNumber = $request->get('page');
		$orderBy = $request->get('orderby');
		$sortOrder = $request->get('sortorder');
		$sourceModule = $request->get('sourceModule');
		$forModule = $request->get('formodule');
		$searchParams = $request->get('searchParams');
		$searchKey = $request->get('search_key');
		$searchValue = $request->get('search_value');

		if (empty($pageNumber)) {
			$pageNumber = 1;
		}

		if (!$this->listViewModel) {
			$this->listViewModel = $this->createListViewModel($request);
		}
		$listViewModel = $this->listViewModel;

		$availableColumns = array_keys($listViewModel->getModule()->getListFields());

		if (!empty($orderBy) && !in_array($orderBy, $availableColumns, true)) {
			$orderBy = $availableColumns ? reset($availableColumns) : null;
			$sortOrder = 'ASC';
		}

		$sortOrder = strtoupper($sortOrder) === 'DESC' ? 'DESC' : 'ASC';
		$nextSortOrder = $sortOrder === 'ASC' ? 'DESC' : 'ASC';
		$sortImage = $sortOrder === 'ASC'
			? 'glyphicon glyphicon-chevron-down'
			: 'glyphicon glyphicon-chevron-up';

		$pagingModel = new \App\Modules\Base\Models\Paging();
		$pagingModel->set('page', $pageNumber);

		if (!empty($searchKey) && !empty($searchValue)) {
			$listViewModel->set('search_key', $searchKey);
			$listViewModel->set('search_value', $searchValue);
		}
		if (!empty($searchParams)) {
			$listViewModel->set('searchParams', $searchParams);
			$viewer->assign('SEARCH_PARAMS', $searchParams);
		}

		if (!empty($orderBy)) {
			$listViewModel->set('orderby', $orderBy);
			$listViewModel->set('sortorder', $sortOrder);
		}
		if (!empty($sourceModule)) {
			$listViewModel->set('sourceModule', $sourceModule);
		}
		if (!empty($forModule)) {
			$listViewModel->set('formodule', $forModule);
		}
		if (!$this->listViewHeaders) {
			$this->listViewHeaders = $listViewModel->getListViewHeaders();
		}
		if (!$this->listViewEntries) {
			$this->listViewEntries = $listViewModel->getListViewEntries($pagingModel);
		}
		$noOfEntries = count($this->listViewEntries);
		if (!isset($this->listViewLinks)) {
			$this->listViewLinks = $listViewModel->getListViewLinks();
		}
		$viewer->assign('LISTVIEW_LINKS', $this->listViewLinks);
		$viewer->assign('MODULE_MODEL', $listViewModel->getModule());

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
		if (!isset($this->listViewCount)) {
			$this->listViewCount = $listViewModel->getListViewCount();
		}
		$totalCount = $this->listViewCount;
		$pagingModel->set('totalCount', (int) $totalCount);
		$pageCount = $pagingModel->getPageCount();
		$startPaginFrom = $pagingModel->getStartPagingFrom();

		$viewer->assign('PAGE_COUNT', $pageCount);
		$viewer->assign('LISTVIEW_COUNT', $totalCount);
		$viewer->assign('START_PAGIN_FROM', $startPaginFrom);
		$viewer->assign('SOURCE_MODULE', $sourceModule);

		$this->prepareListViewHeaderData($viewer, $qualifiedModuleName);
		$viewer->assign('LIST_MAX_ENTRIES_MASS_EDIT', \App\AppConfig::main('listMaxEntriesMassEdit'));
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		
		if ($request->isAjax()) {
			// AJAX handling - return only contents
			$viewer->view('ListViewContent.tpl', $request->getModule(false));
		} else {
			// Full page rendering - use ListView.tpl which extends MainLayout
			$viewer->assign('VIEW', $request->get('view'));
			$viewer->view('ListView.tpl', $request->getModule(false));
		}
	}

	/**
	 * Function to get the list of Script models to be included
	 * @param \App\Http\Vtiger_Request $request
	 * @return <Array> - List of ScriptAsset instances
	 */
	public function getFooterScripts(\App\Http\Vtiger_Request $request)
	{
		$headerScriptInstances = parent::getFooterScripts($request);
		$moduleName = $request->getModule();

		$jsFileNames = array(
			'modules.Base.resources.Vtiger',
			'modules.Base.resources.ListView',
			'modules.Settings.Vtiger.resources.List',
			"modules.Settings.$moduleName.resources.List",
			"modules.Settings.Vtiger.resources.$moduleName",
			'modules.Base.resources.ListSearch',
			"modules.$moduleName.resources.ListSearch"
		);

		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		$headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);
		return $headerScriptInstances;
	}
}
