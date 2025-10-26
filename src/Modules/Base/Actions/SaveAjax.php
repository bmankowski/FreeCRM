<?php

namespace App\Modules\Base\Actions;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * *********************************************************************************** */

class SaveAjax extends \App\Modules\Base\Actions\Save
{

	/**
	 * Function process
	 * @param \App\Http\Vtiger_Request $request
	 */
	public function process(\App\Http\Vtiger_Request $request)
	{
		$recordModel = $this->saveRecord($request);
		$fieldModelList = $recordModel->getModule()->getFields();
		$result = [];
		foreach ($fieldModelList as $fieldName => &$fieldModel) {
			$recordFieldValue = $recordModel->get($fieldName);

			if (is_array($recordFieldValue) && $fieldModel->getFieldDataType() === 'multipicklist') {
				$recordFieldValue = implode(' |##| ', $recordFieldValue);
			} elseif (is_array($recordFieldValue) && in_array($fieldModel->getFieldDataType(), ['sharedOwner', 'taxes'])) {
				$recordFieldValue = implode(',', $recordFieldValue);
			}
			$fieldValue = $displayValue = \App\Modules\Base\Helpers\Util::toSafeHTML($recordFieldValue);
			if ($fieldModel->getFieldDataType() === 'currency') {
				$displayValue = \App\Modules\Base\Helpers\Util::toSafeHTML($fieldModel->getDisplayValue($recordFieldValue, $recordModel->getId()));
			} else {
				$displayValue = $fieldModel->getDisplayValue($recordFieldValue, $recordModel->getId(), $recordModel);
			}
			$result[$fieldName] = ['value' => $fieldValue, 'display_value' => $displayValue];
		}
		$result['_recordLabel'] = $recordModel->getName();
		$result['_recordId'] = $recordModel->getId();
		$recordModel->clearPrivilegesCache();
		$result['isEditable'] = $recordModel->isEditable();

		$response = new \App\Http\Vtiger_Response();
		$response->setEmitType(\App\Http\Vtiger_Response::$EMIT_JSON);
		$response->setResult($result);
		$response->emit();
	}

	/**
	 * Function to get the record model based on the request parameters
	 * @param \App\Http\Vtiger_Request $request
	 * @return \App\Modules\Base\Models\Record or Module specific Record Model instance
	 */
	public function getRecordModelFromRequest(\App\Http\Vtiger_Request $request)
	{
		$recordId = $request->get('record');
		if (!empty($recordId)) {
			$moduleName = $request->getModule();
			$recordModel = $this->record ? $this->record : \App\Modules\Base\Models\Record::getInstanceById($recordId, $moduleName);
			$fieldModel = $recordModel->getModule()->getFieldByName($request->get('field'));
			if ($fieldModel && $fieldModel->isEditable()) {
				$recordModel->set($fieldModel->getName(), $fieldModel->getUITypeModel()->getDBValue($request->get('value'), $recordModel));
			}
		} else {
			$recordModel = parent::getRecordModelFromRequest($request);
		}
		return $recordModel;
	}
}
