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

class MoveReports extends \FreeCRM\Runtime\Vtiger_Action_Controller
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
		$parentModule = 'Reports';
		$reportIdsList = \FreeCRM\Modules\Reports\Models\Record::getRecordsListFromRequest($request);
		$folderId = $request->get('folderid');

		if (!empty($reportIdsList)) {
			foreach ($reportIdsList as $reportId) {
				$reportModel = \FreeCRM\Modules\Reports\Models\Record::getInstanceById($reportId);
				if (!$reportModel->isDefault() && $reportModel->isEditable()) {
					$reportModel->move($folderId);
				} else {
					$reportsMoveDenied[] = \FreeCRM\Runtime\Vtiger_Language_Handler::translate($reportModel->getName(), $parentModule);
				}
			}
		}
		$response = new \FreeCRM\Http\Vtiger_Response();
		if (empty($reportsMoveDenied)) {
			$response->setResult(array(\FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_REPORTS_MOVED_SUCCESSFULLY', $parentModule)));
		} else {
			$response->setError($reportsMoveDenied, \FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_DENIED_REPORTS', $parentModule));
		}

		$response->emit();
	}
}
