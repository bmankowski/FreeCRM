<?php

namespace App\Modules\Base\Views;

/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * ********************************************************************************** */


use App\Http\Vtiger_Request;
class FindDuplicates  extends \App\Modules\Base\Views\Index
{

	public function preProcess(\App\Http\Vtiger_Request $request, $display = true)
	{
		parent::preProcess($request, false);
		$viewer = $this->getViewer($request);
		$this->initializeListViewContents($request, $viewer);
		// MainLayout handles rendering, no separate preProcess template needed
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		$moduleModel = \App\Modules\Base\Models\Module::getInstance($moduleName);
		$this->initializeListViewContents($request, $viewer);
		$viewer->assign('MODULE_MODEL', $moduleModel);
		// Render full page with MainLayout
		$viewer->view('FindDuplicates.tpl', $moduleName);
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
		unset($headerScriptInstances['modules.Base.resources.FindDuplicates']);
		$jsFileNames = [
			'modules.Base.resources.ListView',
			'modules.Base.resources.FindDuplicates',
		];
		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		$headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);
		return $headerScriptInstances;
	}
	/*
	 * Function to initialize the required data in smarty to display the List View Contents
	 */

	public function initializeListViewContents(\App\Http\Vtiger_Request $request, CRM_Viewer $viewer)
	{
		$currentUser = $request->getUser();
		$viewer = $this->getViewer($request);
		$module = $request->getModule();
		$moduleModel = \App\Modules\Base\Models\Module::getInstance($module);

		$massActionLink = array(
			'linktype' => 'LISTVIEWBASIC',
			'linklabel' => 'LBL_DELETE',
			'linkurl' => 'Javascript:Vtiger_FindDuplicates_Js.massDeleteRecords("index.php?module=' . $module . '&action=MassDelete")',
			'linkicon' => ''
		);
		$massActionLinks[] = \App\Modules\Base\Models\Link::getInstanceFromValues($massActionLink);
		$viewer->assign('LISTVIEW_LINKS', $massActionLinks);
		$viewer->assign('MODULE_MODEL', $moduleModel);

		$pageNumber = $request->get('page');
		if (empty($pageNumber)) {
			$pageNumber = '1';
		}
		$pagingModel = new \App\Modules\Base\Models\Paging();
		$pagingModel->set('page', $pageNumber);
		$pageLimit = $pagingModel->getPageLimit();

		$duplicateSearchFields = $request->get('fields');
		$dataModelInstance = \App\Modules\Base\Models\FindDuplicate::getInstance($module);
		$dataModelInstance->set('fields', $duplicateSearchFields);

		$ignoreEmpty = $request->get('ignoreEmpty');
		$ignoreEmptyValue = false;
		if ($ignoreEmpty == 'on' || $ignoreEmpty == 'true' || $ignoreEmpty == '1')
			$ignoreEmptyValue = true;
		$dataModelInstance->set('ignoreEmpty', $ignoreEmptyValue);

		if (!$this->listViewEntries) {
			$this->listViewEntries = $dataModelInstance->getListViewEntries($pagingModel);
		}

		if (!$this->listViewHeaders) {
			$this->listViewHeaders = $dataModelInstance->getListViewHeaders();
		}
		if (!$this->rows) {
			$this->rows = $dataModelInstance->getRecordCount();
			$viewer->assign('TOTAL_COUNT', $this->rows);
		}

		$rowCount = 0;
		foreach ($this->listViewEntries as $group) {
			foreach ($group as $row) {
				$rowCount++;
			}
		}
		//for calculating the page range
		for ($i = 0; $i < $rowCount; $i++)
			$dummyListEntries[] = $i;
		$pagingModel->calculatePageRange($rowCount);

		$totalCount = $this->rows;
		$pagingModel->set('totalCount', (int) $totalCount);
		$pageCount = $pagingModel->getPageCount();
		$startPaginFrom = $pagingModel->getStartPagingFrom();

		$viewer->assign('LISTVIEW_COUNT', $totalCount);
		$viewer->assign('PAGE_COUNT', $pageCount);
		$viewer->assign('START_PAGIN_FROM', $startPaginFrom);
		$viewer->assign('IGNORE_EMPTY', $ignoreEmpty);
		$viewer->assign('LISTVIEW_ENTRIES_COUNT', $rowCount);
		$viewer->assign('LISTVIEW_HEADERS', $this->listViewHeaders);
		$viewer->assign('LISTVIEW_ENTRIES', $this->listViewEntries);
		$viewer->assign('PAGING_MODEL', $pagingModel);
		$viewer->assign('PAGE_NUMBER', $pageNumber);
		$viewer->assign('MODULE', $module);
		$viewer->assign('DUPLICATE_SEARCH_FIELDS', $duplicateSearchFields);

		$customViewModel = \App\Modules\CustomView\Models\Record::getAllFilterByModule($module);
		$viewer->assign('VIEW_NAME', $customViewModel->getId());
	}

	/**
	 * Function returns the number of records for the current filter
	 * @param \App\Http\Vtiger_Request $request
	 */
	public function getRecordsCount(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$duplicateSearchFields = $request->get('fields');
		$dataModelInstance = \App\Modules\Base\Models\FindDuplicate::getInstance($moduleName);

		$ignoreEmpty = $request->get('ignoreEmpty');
		$ignoreEmptyValue = false;
		if ($ignoreEmpty == 'on' || $ignoreEmpty == 'true' || $ignoreEmpty == '1')
			$ignoreEmptyValue = true;
		$dataModelInstance->set('ignoreEmpty', $ignoreEmptyValue);

		$dataModelInstance->set('fields', $duplicateSearchFields);
		$count = $dataModelInstance->getRecordCount();

		$result = [];
		$result['module'] = $moduleName;
		$result['count'] = $count;

		$response = new \App\Http\Vtiger_Response();
		$response->setEmitType(Vtiger_Response::$EMIT_JSON);
		$response->setResult($result);
		$response->emit();
	}
}
