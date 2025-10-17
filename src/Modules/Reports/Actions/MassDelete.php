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

class MassDelete extends \App\Runtime\Vtiger_Action_Controller
{

	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		$currentUserPriviligesModel = \App\Modules\Users\Models\Privileges::getCurrentUserPrivilegesModel();
		if (!$currentUserPriviligesModel->hasModulePermission($request->getModule())) {
			throw new \Exception\NoPermitted('LBL_PERMISSION_DENIED');
		}
	}

	public function preProcess(\App\Http\Vtiger_Request $request)
	{
		return true;
	}

	public function postProcess(\App\Http\Vtiger_Request $request)
	{
		return true;
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$parentModule = 'Reports';
		$recordIds = \App\Modules\Reports\Models\Record::getRecordsListFromRequest($request);

		$reportsDeleteDenied = array();
		foreach ($recordIds as $recordId) {
			$recordModel = \App\Modules\Reports\Models\Record::getInstanceById($recordId);
			if (!$recordModel->isDefault() && $recordModel->isEditable()) {
				$success = $recordModel->delete();
				if (!$success) {
					$reportsDeleteDenied[] = \App\Runtime\Vtiger_Language_Handler::translate($recordModel->getName(), $parentModule);
				}
			} else {
				$reportsDeleteDenied[] = \App\Runtime\Vtiger_Language_Handler::translate($recordModel->getName(), $parentModule);
			}
		}

		$response = new \App\Http\Vtiger_Response();
		if (empty($reportsDeleteDenied)) {
			$response->setResult(array(\App\Runtime\Vtiger_Language_Handler::translate('LBL_REPORTS_DELETED_SUCCESSFULLY', $parentModule)));
		} else {
			$response->setError($reportsDeleteDenied, \App\Runtime\Vtiger_Language_Handler::translate('LBL_DENIED_REPORTS', $parentModule));
		}

		$response->emit();
	}
}
