<?php

namespace App\Modules\Vtiger\Actions;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * *********************************************************************************** */

class MassSave extends \App\Runtime\Vtiger_Action_Controller
{

	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		$currentUserPriviligesModel = \App\Modules\Users\Models\Privileges::getCurrentUserPrivilegesModel();
		if (!$currentUserPriviligesModel->hasModuleActionPermission($request->getModule(), 'Save')) {
			throw new \Exception\NoPermitted('LBL_PERMISSION_DENIED');
		}
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$recordModels = $this->getRecordModelsFromRequest($request);
		$allRecordSave = true;
		foreach ($recordModels as $recordId => &$recordModel) {
			if (\App\Modules\Users\Models\Privileges::isPermitted($moduleName, 'Save', $recordId)) {
				$recordModel->save();
			} else {
				$allRecordSave = false;
			}
		}

		$response = new \App\Http\Vtiger_Response();
		$response->setResult($allRecordSave);
		$response->emit();
	}

	/**
	 * Function to get the record model based on the request parameters
	 * @param \App\Http\Vtiger_Request $request
	 * @return array - List of \App\Modules\Vtiger\Models\Record instances
	 */
	public function getRecordModelsFromRequest(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$moduleModel = \App\Modules\Vtiger\Models\Module::getInstance($moduleName);
		$recordIds = \App\Modules\Vtiger\Actions\Mass::getRecordsListFromRequest($request);
		$recordModels = [];

		foreach ($recordIds as &$recordId) {
			$recordModel = \App\Modules\Vtiger\Models\Record::getInstanceById($recordId, $moduleModel);
			if (!$recordModel->isEditable()) {
				continue;
			}
			$fieldModelList = $moduleModel->getFields();
			foreach ($fieldModelList as $fieldName => &$fieldModel) {
				if (!$fieldModel->isEditable()) {
					continue;
				}
				if ($request->has($fieldName)) {
					if ($fieldModel->get('uitype') === 300) {
						$recordModel->set($fieldName, $request->getForHtml($fieldName, null));
					} else {
						$recordModel->set($fieldName, $fieldModel->getUITypeModel()->getDBValue($request->get($fieldName, null), $recordModel));
					}
				}
			}
			$recordModels[$recordId] = $recordModel;
		}
		return $recordModels;
	}
}
