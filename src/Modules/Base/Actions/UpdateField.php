<?php

namespace App\Modules\Base\Actions;

/**
 * Update field with current time
 * @package YetiForce.Action
 * @license licenses/License.html
 * @author Tomasz Kur <t.kur@yetiforce.com>
 */
class UpdateField extends \App\Base\Controllers\BaseActionController
{

	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		$recordId = $request->get('record');
		$moduleName = $request->getModule();
		$fieldName = $request->get('fieldName');
		if (!\App\Privilege::isPermitted($moduleName, 'EditView', $recordId)) {
			throw new \App\Exceptions\NoPermittedToRecord('LBL_PERMISSION_DENIED');
		}
		$recordModel = \App\Modules\Base\Models\Record::getInstanceById($recordId);
		if (!$recordModel->isEditable()) {
			throw new \App\Exceptions\NoPermittedToRecord('LBL_PERMISSION_DENIED');
		}
		if (!\App\Field::getFieldPermission($moduleName, $fieldName)) {
			throw new \App\Exceptions\NoPermittedToRecord('LBL_PERMISSION_DENIED');
		}
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$fieldName = $request->get('fieldName');
		$fieldModel = \App\Modules\Base\Models\Field::getInstance($fieldName, \App\Modules\Base\Models\Module::getInstance($moduleName));
		$updateField = \App\Modules\Base\Helpers\UpdaterField::getInstance();
		$updateField->setFieldModel($fieldModel);
		$value = $updateField->getValue();
		$recordModel = \App\Modules\Base\Models\Record::getInstanceById($request->get('record'), $moduleName);
		$recordModel->set($fieldName, $value);
		$recordModel->save();
		$result[$fieldName] = $value;
		$response = new \App\Http\Vtiger_Response();
		$response->setResult($result);
		$response->emit();
	}
}
