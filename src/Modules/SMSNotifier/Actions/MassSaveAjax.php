<?php

namespace FreeCRM\Modules\SMSNotifier\Actions;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * *********************************************************************************** */

class MassSaveAjax extends \FreeCRM\Runtime\Vtiger_Action_Controller
{

	public function checkPermission(\FreeCRM\Http\Vtiger_Request $request)
	{
		$currentUserPriviligesModel = \FreeCRM\Modules\Users\Models\Privileges::getCurrentUserPrivilegesModel();
		if (!$currentUserPriviligesModel->hasModuleActionPermission($request->getModule(), 'Save')) {
			throw new \Exception\NoPermitted('LBL_PERMISSION_DENIED');
		}
	}

	/**
	 * Function that saves SMS records
	 * @param Vtiger_Request $request
	 */
	public function process(\FreeCRM\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();

		$currentUserModel = \FreeCRM\Modules\Users\Models\Record::getCurrentUserModel();
		$recordIds = $this->getRecordsListFromRequest($request);
		$phoneFieldList = $request->get('fields');
		$message = $request->get('message');

		foreach ($recordIds as $recordId) {
			$recordModel = \FreeCRM\Modules\Vtiger\Models\Record::getInstanceById($recordId);
			$numberSelected = false;
			foreach ($phoneFieldList as $fieldname) {
				$fieldValue = $recordModel->get($fieldname);
				if (!empty($fieldValue)) {
					$toNumbers[] = $fieldValue;
					$numberSelected = true;
				}
			}
			if ($numberSelected) {
				$recordIds[] = $recordId;
			}
		}

		$response = new \FreeCRM\Http\Vtiger_Response();

		if (!empty($toNumbers)) {
			$sourceModule=$request->get('source_module');
			\FreeCRM\Modules\SMSNotifier\Models\Record::SendSMS($message, $toNumbers, $currentUserModel->getId(), $recordIds, $sourceModule);
			$response->setResult(true);
		} else {
			$response->setResult(false);
		}
		return $response;
	}
}
