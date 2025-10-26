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

class MoveReports extends \App\Runtime\BaseActionController
{

	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		$currentUserPriviligesModel = \App\Modules\Users\Models\Privileges::getCurrentUserPrivilegesModel();
		if (!$currentUserPriviligesModel->hasModulePermission($request->getModule())) {
			throw new \Exception\NoPermitted('LBL_PERMISSION_DENIED');
		}
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$parentModule = 'Reports';
		$reportIdsList = \App\Modules\Reports\Models\Record::getRecordsListFromRequest($request);
		$folderId = $request->get('folderid');

		if (!empty($reportIdsList)) {
			foreach ($reportIdsList as $reportId) {
				$reportModel = \App\Modules\Reports\Models\Record::getInstanceById($reportId);
				if (!$reportModel->isDefault() && $reportModel->isEditable()) {
					$reportModel->move($folderId);
				} else {
					$reportsMoveDenied[] = \App\Runtime\Vtiger_Language_Handler::translate($reportModel->getName(), $parentModule);
				}
			}
		}
		$response = new \App\Http\Vtiger_Response();
		if (empty($reportsMoveDenied)) {
			$response->setResult(array(\App\Runtime\Vtiger_Language_Handler::translate('LBL_REPORTS_MOVED_SUCCESSFULLY', $parentModule)));
		} else {
			$response->setError($reportsMoveDenied, \App\Runtime\Vtiger_Language_Handler::translate('LBL_DENIED_REPORTS', $parentModule));
		}

		$response->emit();
	}
}
