<?php

namespace FreeCRM\Modules\Reports\Actions;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * *********************************************************************************** */

class CheckDuplicate extends \FreeCRM\Runtime\Vtiger_Action_Controller
{

	public function checkPermission(\FreeCRM\Http\Vtiger_Request $request)
	{
		$currentUserPriviligesModel = \FreeCRM\Modules\Users\Models\Privileges::getCurrentUserPrivilegesModel();
		if (!$currentUserPriviligesModel->hasModulePermission($request->getModule())) {
			throw new \Exception\NoPermitted('LBL_PERMISSION_DENIED');
		}
	}

	public function process(\FreeCRM\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$reportName = $request->get('reportname');
		$record = $request->get('record');

		if ($record) {
			$recordModel = \FreeCRM\Modules\Vtiger\Models\Record::getInstanceById($record, $moduleName);
		} else {
			$recordModel = \FreeCRM\Modules\Vtiger\Models\Record::getCleanInstance($moduleName);
		}

		$recordModel->set('reportname', $reportName);
		$recordModel->set('reportid', $record);
		$recordModel->set('isDuplicate', $request->get('isDuplicate'));

		if (!$recordModel->checkDuplicate()) {
			$result = array('success' => false);
		} else {
			$result = array('success' => true, 'message' => \FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_DUPLICATES_EXIST', $moduleName));
		}
		$response = new \FreeCRM\Http\Vtiger_Response();
		$response->setResult($result);
		$response->emit();
	}
}
