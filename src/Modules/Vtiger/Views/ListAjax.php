<?php

namespace App\Modules\Vtiger\Views;

/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * ********************************************************************************** */


use App\Http\Vtiger_Request;
class ListAjax  extends \App\Modules\Vtiger\Views\Index
{

	public function __construct()
	{
		parent::__construct();
		$this->exposeMethod('getListViewCount');
		$this->exposeMethod('getRecordsCount');
		$this->exposeMethod('getPageCount');
	}

	public function preProcess(\App\Http\Vtiger_Request $request, $display = true)
	{
		return true;
	}

	public function postProcess(\App\Http\Vtiger_Request $request)
	{
		return true;
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$mode = $request->get('mode');
		if (!empty($mode)) {
			$this->invokeExposedMethod($mode, $request);
			return;
		}
	}

	/**
	 * Function to get the page count for list
	 * @return total number of pages
	 */
	public function getPageCount(\App\Http\Vtiger_Request $request)
	{
		$listViewCount = $this->getListViewCount($request);
		$pagingModel = new \App\Modules\Vtiger\Models\Paging();
		$pageLimit = $pagingModel->getPageLimit();
		$pageCount = ceil((int) $listViewCount / (int) $pageLimit);

		if ($pageCount == 0) {
			$pageCount = 1;
		}
		$result = [];
		$result['page'] = $pageCount;
		$result['numberOfRecords'] = $listViewCount;
		$response = new Vtiger_Response();
		$response->setResult($result);
		$response->emit();
	}

	/**
	 * Function returns the number of records for the current filter
	 * @param Vtiger_Request $request
	 */
	public function getRecordsCount(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$cvId = \App\CustomView::getInstance($moduleName)->getViewId();
		$count = $this->getListViewCount($request);

		$result = [];
		$result['module'] = $moduleName;
		$result['viewname'] = $cvId;
		$result['count'] = $count;

		$response = new Vtiger_Response();
		$response->setEmitType(Vtiger_Response::$EMIT_JSON);
		$response->setResult($result);
		$response->emit();
	}

	/**
	 * Function to get listView count
	 * @param Vtiger_Request $request
	 */
	public function getListViewCount(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		if (!$this->listViewModel) {
			$cvId = \App\CustomView::getInstance($moduleName)->getViewId();
			if (!$cvId) {
				$cvId = 0;
			}
			$this->listViewModel = \App\Modules\Vtiger\Models\ListView::getInstance($moduleName, $cvId);
		}
		$searchKey = $request->get('search_key');
		$searchValue = $request->get('search_value');
		$operator = $request->get('operator');
		if (!empty($operator)) {
			$this->listViewModel->set('operator', $operator);
		}
		if (!empty($searchKey) && !empty($searchValue)) {
			$this->listViewModel->set('search_key', $searchKey);
			$this->listViewModel->set('search_value', $searchValue);
		}
		$searchParmams = $request->get('search_params');
		if (!empty($searchParmams) && is_array($searchParmams)) {
			$transformedSearchParams = $this->listViewModel->get('query_generator')->parseBaseSearchParamsToCondition($searchParmams);
			$this->listViewModel->set('search_params', $transformedSearchParams);
		}
		return $this->listViewModel->getListViewCount();
	}
}
