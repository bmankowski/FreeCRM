<?php

namespace FreeCRM\Modules\ModComments\Actions;

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

	public function process(\FreeCRM\Http\Vtiger_Request $request)
	{
		$recordModels = $this->getRecordModelsFromRequest($request);
		foreach ($recordModels as &$recordModel) {
			$recordModel->save();
		}
		$response = new \FreeCRM\Http\Vtiger_Response();
		$response->setResult(true);
		$response->emit();
	}

	/**
	 * Function to get the record model based on the request parameters
	 * @param Vtiger_Request $request
	 * @return \FreeCRM\Modules\Vtiger\Models\Record or Module specific Record Model instance
	 */
	private function getRecordModelsFromRequest(\FreeCRM\Http\Vtiger_Request $request)
	{

		$moduleName = $request->getModule();
		$recordIds = $this->getRecordsListFromRequest($request);
		$recordModels = [];
		$currentUserModel = \FreeCRM\Modules\Users\Models\Record::getCurrentUserModel();
		foreach ($recordIds as &$recordId) {
			$recordModel = \FreeCRM\Modules\Vtiger\Models\Record::getCleanInstance($moduleName);
			$recordModel->set('commentcontent', $request->get('commentcontent'));
			$recordModel->set('related_to', $recordId);
			$recordModel->set('assigned_user_id', $currentUserModel->getId());
			$recordModels[$recordId] = $recordModel;
		}
		return $recordModels;
	}
}
