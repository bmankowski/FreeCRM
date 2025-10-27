<?php

namespace App\Modules\Users\Actions;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * *********************************************************************************** */

class MassSave extends \App\Base\Controllers\BaseActionController
{

	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		$currentUserModel = $request->getUser();
		if (!$currentUserModel->isAdminUser()) {
			throw new \App\Exceptions\NoPermitted('LBL_PERMISSION_DENIED');
		}
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$moduleModel = \App\Modules\Base\Models\Module::getInstance($moduleName);
		$recordModels = $this->getRecordModelsFromRequest($request);
		foreach ($recordModels as $recordId => $recordModel) {
			if (\App\Modules\Users\Models\Privileges::isPermitted($moduleName, 'Save', $recordId)) {
				$recordModel->save();
			}
		}

		$response = new \App\Http\Vtiger_Response();
		$response->setResult(true);
		$response->emit();
	}

	/**
	 * Function to get the record model based on the request parameters
	 * @param \App\Http\Vtiger_Request $request
	 * @return \App\Modules\Base\Models\Record or Module specific Record Model instance
	 */
	public function getRecordModelsFromRequest(\App\Http\Vtiger_Request $request)
	{

		$moduleName = $request->getModule();
		$moduleModel = \App\Modules\Base\Models\Module::getInstance($moduleName);
		$recordIds = $this->getRecordsListFromRequest($request);

		if (empty($recordIds) && $request->get('selected_ids') == 'all') {
			$db = \App\Database\PearDatabase::getInstance();

			$sql = "SELECT `id` FROM `vtiger_users`";
			$result = $db->query($sql, true);
			$uNum = $db->num_rows($result);

			if ($uNum > 0) {
				$recordIds = array();
				for ($i = 0; $i < $uNum; $i++) {
					$recordIds[] = $db->query_result($result, $i, 'id');
				}
			}
		}
		$recordModels = array();

		$fieldModelList = $moduleModel->getFields();
		foreach ($recordIds as $recordId) {
			$recordModel = \App\Modules\Base\Models\Record::getInstanceById($recordId, $moduleModel);
			$recordModel->set('id', $recordId);

			foreach ($fieldModelList as $fieldName => $fieldModel) {
				$fieldValue = $request->get($fieldName, null);
				$fieldDataType = $fieldModel->getFieldDataType();
				if ($fieldDataType == 'time') {
					$fieldValue = \App\Modules\Base\UiTypes\Time::getTimeValueWithSeconds($fieldValue);
				}
				if (isset($fieldValue) && $fieldValue != null) {
					if (!is_array($fieldValue)) {
						$fieldValue = trim($fieldValue);
					}
					$recordModel->set($fieldName, $fieldValue);
				} else {
					$uiType = $fieldModel->get('uitype');
					if ($uiType == 70) {
						$recordModel->set($fieldName, $recordModel->get($fieldName));
					} else {
						$uiTypeModel = $fieldModel->getUITypeModel();
						$recordModel->set($fieldName, $uiTypeModel->getDBValue($recordModel->get($fieldName), $recordModel));
					}
				}
			}
			$recordModels[$recordId] = $recordModel;
		}
		return $recordModels;
	}
}
