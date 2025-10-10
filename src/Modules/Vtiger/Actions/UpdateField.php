<?php

namespace FreeCRM\Modules\Vtiger\Actions;

/**
 * Update field with current time
 * @package YetiForce.Action
 * @license licenses/License.html
 * @author Tomasz Kur <t.kur@yetiforce.com>
 */
class UpdateField extends \FreeCRM\Runtime\Vtiger_Action_Controller
{

	public function checkPermission(\FreeCRM\Http\Vtiger_Request $request)
	{
		$recordId = $request->get('record');
		$moduleName = $request->getModule();
		$fieldName = $request->get('fieldName');
		if (!\App\Privilege::isPermitted($moduleName, 'EditView', $recordId)) {
			throw new \Exception\NoPermittedToRecord('LBL_PERMISSION_DENIED');
		}
		$recordModel = \FreeCRM\Modules\Vtiger\Models\Record::getInstanceById($recordId);
		if (!$recordModel->isEditable()) {
			throw new \Exception\NoPermittedToRecord('LBL_PERMISSION_DENIED');
		}
		if (!\App\Field::getFieldPermission($moduleName, $fieldName)) {
			throw new \Exception\NoPermittedToRecord('LBL_PERMISSION_DENIED');
		}
	}

	public function process(\FreeCRM\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$fieldName = $request->get('fieldName');
		$fieldModel = \FreeCRM\Modules\Vtiger\Models\Field::getInstance($fieldName, \FreeCRM\Modules\Vtiger\Models\Module::getInstance($moduleName));
		$updateField = Vtiger_UpdaterField_Helper::getInstance();
		$updateField->setFieldModel($fieldModel);
		$value = $updateField->getValue();
		$recordModel = \FreeCRM\Modules\Vtiger\Models\Record::getInstanceById($request->get('record'), $moduleName);
		$recordModel->set($fieldName, $value);
		$recordModel->save();
		$result[$fieldName] = $value;
		$response = new \FreeCRM\Http\Vtiger_Response();
		$response->setResult($result);
		$response->emit();
	}
}
