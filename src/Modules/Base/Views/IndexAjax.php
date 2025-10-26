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
class IndexAjax  extends \App\Modules\Base\Views\Index
{

	public function __construct()
	{
		parent::__construct();
		$this->exposeMethod('showActiveRecords');
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
	/*
	 * Function to show the recently modified or active records for the given module
	 */

	public function showActiveRecords(\App\Http\Vtiger_Request $request)
	{
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();

		$moduleModel = \App\Modules\Base\Models\Module::getInstance($moduleName);
		$recentRecords = $moduleModel->getRecentRecords();

		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('RECORDS', $recentRecords);

		echo $viewer->view('RecordNamesList.tpl', $moduleName, true);
	}

	public function getRecordsListFromRequest(\App\Http\Vtiger_Request $request)
	{
		$cvId = $request->get('cvid');
		$selectedIds = $request->get('selected_ids');
		$excludedIds = $request->get('excluded_ids');

		if (!empty($selectedIds) && $selectedIds != 'all') {
			if (!empty($selectedIds) && count($selectedIds) > 0) {
				return $selectedIds;
			}
		}
		if (!empty($cvId) && $cvId == 'undefined') {
			$sourceModule = $request->get('sourceModule');
			$cvId = \App\Modules\CustomView\Models\Record::getAllFilterByModule($sourceModule)->getId();
		}

		$customViewModel = \App\Modules\CustomView\Models\Record::getInstanceById($cvId);
		if ($customViewModel) {
			$searchKey = $request->get('search_key');
			$searchValue = $request->get('search_value');
			$operator = $request->get('operator');
			if (!empty($operator)) {
				$customViewModel->set('operator', $operator);
				$customViewModel->set('search_key', $searchKey);
				$customViewModel->set('search_value', $searchValue);
			}
			if ($request->has('search_params')) {
				$customViewModel->set('search_params', $request->get('search_params'));
			}
			return $customViewModel->getRecordIds($excludedIds);
		}
	}
}
