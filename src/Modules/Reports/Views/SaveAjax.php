<?php

namespace App\Modules\Reports\Views;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * *********************************************************************************** */


use App\Http\Vtiger_Request;
class SaveAjax  extends \App\Modules\Vtiger\Views\Index
{

	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		$record = $request->get('record');
		if (!$record) {
			throw new \App\Exceptions\NoPermitted('LBL_PERMISSION_DENIED');
		}
		$reportModel = \App\Modules\Reports\Models\Record::getCleanInstance($record);

		$currentUserPriviligesModel = \App\Modules\Users\Models\Privileges::getCurrentUserPrivilegesModel();
		if (!$currentUserPriviligesModel->hasModulePermission($request->getModule()) && !$reportModel->isEditable()) {
			throw new \App\Exceptions\NoPermittedToRecord('LBL_PERMISSION_DENIED');
		}
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$mode = $request->getMode();
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();

		$record = $request->get('record');
		$reportModel = \App\Modules\Reports\Models\Record::getInstanceById($record);

		$reportModel->setModule('Reports');

		$reportModel->set('advancedFilter', $request->get('advanced_filter'));

		$page = $request->get('page');
		$pagingModel = new \App\Modules\Vtiger\Models\Paging();
		$pagingModel->set('page', $page);
		$pagingModel->set('limit', \App\Modules\Reports\Views\Detail::REPORT_LIMIT);

		if ($mode === 'save') {
			$reportModel->saveAdvancedFilters();
			$reportData = $reportModel->getReportData($pagingModel);
			$data = $reportData['data'];
		} else if ($mode === 'generate') {
			$reportData = $reportModel->generateData($pagingModel);
			$data = $reportData['data'];
		}
		$calculation = $reportModel->generateCalculationData();

		$viewer->assign('PRIMARY_MODULE', $reportModel->getPrimaryModule());
		$viewer->assign('CALCULATION_FIELDS', $calculation);
		$viewer->assign('DATA', $data);
		$viewer->assign('RECORD_ID', $record);
		$viewer->assign('PAGING_MODEL', $pagingModel);
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('NEW_COUNT', $reportData['count']);
		$viewer->assign('REPORT_RUN_INSTANCE', ReportRun::getInstance($record));
		$viewer->view('ReportContents.tpl', $moduleName);
	}

	public function validateRequest(\App\Http\Vtiger_Request $request)
	{
		$request->validateWriteAccess();
	}
}
