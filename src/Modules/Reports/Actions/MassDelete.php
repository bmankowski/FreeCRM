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

class MassDelete extends \FreeCRM\Runtime\Vtiger_Action_Controller
{

	public function checkPermission(\FreeCRM\Http\Vtiger_Request $request)
	{
		$currentUserPriviligesModel = \FreeCRM\Modules\Users\Models\Privileges::getCurrentUserPrivilegesModel();
		if (!$currentUserPriviligesModel->hasModulePermission($request->getModule())) {
			throw new \Exception\NoPermitted('LBL_PERMISSION_DENIED');
		}
	}

	public function preProcess(\FreeCRM\Http\Vtiger_Request $request)
	{
		return true;
	}

	public function postProcess(\FreeCRM\Http\Vtiger_Request $request)
	{
		return true;
	}

	public function process(\FreeCRM\Http\Vtiger_Request $request)
	{
		$parentModule = 'Reports';
		$recordIds = \FreeCRM\Modules\Reports\Models\Record::getRecordsListFromRequest($request);

		$reportsDeleteDenied = array();
		foreach ($recordIds as $recordId) {
			$recordModel = \FreeCRM\Modules\Reports\Models\Record::getInstanceById($recordId);
			if (!$recordModel->isDefault() && $recordModel->isEditable()) {
				$success = $recordModel->delete();
				if (!$success) {
					$reportsDeleteDenied[] = vtranslate($recordModel->getName(), $parentModule);
				}
			} else {
				$reportsDeleteDenied[] = vtranslate($recordModel->getName(), $parentModule);
			}
		}

		$response = new \FreeCRM\Http\Vtiger_Response();
		if (empty($reportsDeleteDenied)) {
			$response->setResult(array(vtranslate('LBL_REPORTS_DELETED_SUCCESSFULLY', $parentModule)));
		} else {
			$response->setError($reportsDeleteDenied, vtranslate('LBL_DENIED_REPORTS', $parentModule));
		}

		$response->emit();
	}
}
