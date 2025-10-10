<?php

namespace FreeCRM\Modules\Vtiger\Actions;

/**
 * EditFieldByModal Class
 * @package YetiForce.Action
 * @license licenses/License.html
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 */
class EditFieldByModal extends \FreeCRM\Runtime\Vtiger_Action_Controller
{

	public function process(\FreeCRM\Http\Vtiger_Request $request)
	{
		$params = $request->get('param');
		$moduleName = $request->getModule();
		$recordId = $params['record'];
		$state = $params['state'];
		$fieldName = $params['fieldName'];

		$recordModel = \FreeCRM\Modules\Vtiger\Models\Record::getInstanceById($recordId, $moduleName);
		$recordModel->set('id', $recordId);
		$recordModel->set($fieldName, $state);
		$recordModel->save();

		$response = new \FreeCRM\Http\Vtiger_Response();
		$response->setResult(['success' => true]);
		$response->emit();
	}
}
