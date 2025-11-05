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

		$viewer = $this->getViewer($request);
		$this->initializeListViewContents($request, $viewer);
		$sourceModule = $request->get('sourceModule');
		$viewer->assign('SOURCE_MODULE', $sourceModule);
		// MainLayout handles rendering, no separate preProcess template needed
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
	/*
	 * Function to initialize the required data in smarty to display the List View Contents
	 */

	public function initializeListViewContents(\App\Http\Vtiger_Request $request, \App\Runtime\CRM_Viewer $viewer)
	{
		$moduleName = $request->getModule();
		$qualifiedModuleName = $request->getModule(false);
		$pageNumber = $request->get('page');
		$orderBy = $request->get('orderby');
		$sortOrder = $request->get('sortorder');
		$sourceModule = $request->get('sourceModule');
		$forModule = $request->get('formodule');
		$searchParams = $request->get('searchParams');
		$searchKey = $request->get('search_key');
		$searchValue = $request->get('search_value');

		if ($sortOrder == "ASC") {
			$nextSortOrder = "DESC";
			$sortImage = "glyphicon glyphicon-chevron-down";
		} else {
			$nextSortOrder = "ASC";
			$sortImage = "glyphicon glyphicon-chevron-up";
		}
		if (empty($pageNumber)) {
			$pageNumber = 1;
		}

		if (!$this->listViewModel) {
			$this->listViewModel = \App\Modules\Settings\Base\Models\ListView::getInstance($qualifiedModuleName);
		}
		$listViewModel = $this->listViewModel;

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
