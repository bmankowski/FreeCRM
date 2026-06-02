<?php

namespace App\Modules\Reports\Actions;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * *********************************************************************************** */

class DetailAjax extends \App\Base\Controllers\BaseActionController
{

	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		$currentUserPriviligesModel = \App\Modules\Users\Models\Privileges::getCurrentUserPrivilegesModel();
		if (!$currentUserPriviligesModel->hasModulePermission($request->getModule())) {
			throw new \App\Exceptions\NoPermitted('LBL_PERMISSION_DENIED');
		}
	}

	public function __construct()
	{
		parent::__construct();
		$this->exposeMethod('getRecordsCount');
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
	 * Function to get related Records count from this relation
	 * @param \App\Http\Vtiger_Request $request
	 * @return mixed Number of record from this relation
	 */
	public function getRecordsCount(\App\Http\Vtiger_Request $request)
	{
		$record = $request->get('record');
		$reportModel = \App\Modules\Reports\Models\Record::getInstanceById($record);
		$reportModel->setModule('Reports');
		$reportModel->set('advancedFilter', $request->get('advanced_filter'));

		$advFilterSql = $reportModel->getAdvancedFilterSQL();
		$query = $reportModel->getReportSQL($advFilterSql, 'PDF');
		$countQuery = $reportModel->generateCountQuery($query);

		$count = $reportModel->getReportsCount($countQuery);
		$response = new \App\Http\Vtiger_Response();
		$response->setResult($count);
		$response->emit();
	}
}
