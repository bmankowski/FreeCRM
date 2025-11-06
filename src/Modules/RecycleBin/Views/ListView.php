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


use App\Http\Vtiger_Request;
class ListView extends \App\Modules\Base\Views\Index
{
	protected $listViewHeaders = [];
	protected $listViewEntries = [];
	protected $listViewCount = 0;

	public function preProcess(\App\Http\Vtiger_Request $request, $display = true)
	{
		parent::preProcess($request, false);
		// MainLayout.tpl handles rendering, no separate preProcess template needed
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();

		$moduleModel = \App\Modules\RecycleBin\Models\Module::getInstance($moduleName);
		$linkParams = array('MODULE' => $moduleName, 'ACTION' => $request->get('view'));
		$quickLinkModels = $moduleModel->getSideBarLinks($linkParams);

		// Process sidebar links to determine active link
		$activeLinkLabel = $this->processSidebarLinks($quickLinkModels, $request);

		$viewer->assign('MODULE_MODEL', $moduleModel);
		$viewer->assign('QUICK_LINKS', $quickLinkModels);
		$viewer->assign('ACTIVE_SIDEBAR_LINK', $activeLinkLabel);

		$this->initializeListViewContents($request, $viewer);
		$viewer->view('ListView.tpl', $request->getModule());
	}

	public function postProcess(\App\Http\Vtiger_Request $request)
	{
		// MainLayout.tpl handles footer rendering, no separate postProcess template needed
		parent::postProcess($request);
	}
	/*
	 * Function to initialize the required data in smarty to display the List View Contents
	 */

	public function initializeListViewContents(\App\Http\Vtiger_Request $request, \App\Runtime\CRM_Viewer $viewer)
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

		$moduleModel = \App\Modules\RecycleBin\Models\Module::getInstance($moduleName);
		//If sourceModule is empty, pick the first module name from the list
		if (empty($sourceModule)) {
			foreach ($moduleModel->getAllModuleList() as $model) {
				$sourceModule = $model->get('name');
				break;
			}
		}
		$listViewModel = \App\Modules\RecycleBin\Models\ListView::getInstance($moduleName, $sourceModule);

		$linkParams = array('MODULE' => $moduleName, 'ACTION' => $request->get('view'));
		$linkModels = $moduleModel->getListViewMassActions($linkParams);

		$pagingModel = new \App\Modules\Base\Models\Paging();
		$pagingModel->set('page', $pageNumber);
		if (empty($orderBy) && empty($sortOrder)) {
			$moduleInstance = \App\CRMEntity::getInstance($moduleName);
			$orderBy = isset($moduleInstance->default_order_by) ? $moduleInstance->default_order_by : 'modifiedtime';
			$sortOrder = isset($moduleInstance->default_sort_order) ? $moduleInstance->default_sort_order : 'DESC';
		}
		if (!empty($orderBy)) {
			$listViewModel->set('orderby', $orderBy);
			$listViewModel->set('sortorder', $sortOrder);
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

		$viewer->assign('LISTVIEW_LINKS', $moduleModel->getListViewLinks(false));
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
			'modules.Base.resources.CkEditor'
		);
		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		$headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);
		return $headerScriptInstances;
	}

	/**
	 * Function to get the page count for list
	 * @return total number of pages
	 */
	public function getPageCount(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$sourceModule = $request->get('sourceModule');
		$listViewModel = \App\Modules\RecycleBin\Models\ListView::getInstance($moduleName, $sourceModule);

		$listViewCount = $listViewModel->getListViewCount($request);
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

		$count = $listViewModel->getListViewCount();

		$result = array();
		$result['module'] = $moduleName;
		$result['count'] = $count;

		$response = new \App\Http\Vtiger_Response();
		$response->setEmitType(Vtiger_Response::$EMIT_JSON);
		$response->setResult($result);
		$response->emit();
	}
}
