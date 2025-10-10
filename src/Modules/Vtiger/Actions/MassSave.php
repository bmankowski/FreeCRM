<?php

namespace FreeCRM\Modules\Vtiger\Actions;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * *********************************************************************************** */

class MassSave extends \FreeCRM\Runtime\Vtiger_Action_Controller
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
		$moduleName = $request->getModule();
		$recordModels = $this->getRecordModelsFromRequest($request);
		$allRecordSave = true;
		foreach ($recordModels as $recordId => &$recordModel) {
			if (\FreeCRM\Modules\Users\Models\Privileges::isPermitted($moduleName, 'Save', $recordId)) {
				$recordModel->save();
			} else {
				$allRecordSave = false;
			}
		}

		$response = new \FreeCRM\Http\Vtiger_Response();
		$response->setResult($allRecordSave);
		$response->emit();
	}

	/**
	 * Function to get the record model based on the request parameters
	 * @param Vtiger_Request $request
	 * @return array - List of \FreeCRM\Modules\Vtiger\Models\Record instances
	 */
	public function getRecordModelsFromRequest(\FreeCRM\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$moduleModel = \FreeCRM\Modules\Vtiger\Models\Module::getInstance($moduleName);
		$recordIds = Vtiger_Mass_Action::getRecordsListFromRequest($request);
		$recordModels = [];

		foreach ($recordIds as &$recordId) {
			$recordModel = \FreeCRM\Modules\Vtiger\Models\Record::getInstanceById($recordId, $moduleModel);
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
