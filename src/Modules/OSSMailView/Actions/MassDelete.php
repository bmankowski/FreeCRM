<?php

namespace App\Modules\OSSMailView\Actions;

/**
 * Mass delete action class
 * @package YetiForce.Action
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */
class MassDelete extends \App\Runtime\Vtiger_Action_Controller
{

	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		$currentUserPriviligesModel = \App\Modules\Users\Models\Privileges::getCurrentUserPrivilegesModel();
		if (!$currentUserPriviligesModel->hasModulePermission($request->getModule())) {
			throw new \Exception\NoPermitted('LBL_PERMISSION_DENIED');
		}
	}

	public function preProcess(\App\Http\Vtiger_Request $request)
	{
		return true;
	}

	public function postProcess(\App\Http\Vtiger_Request $request)
	{
		return true;
	}

	public function process(\App\Http\Vtiger_Request $request)
	{

		$moduleName = $request->getModule();
		$recordModel = new OSSMailView_Record_Model();
		$recordModel->setModule($moduleName);

		$recordIds = $this->getRecordsListFromRequest($request);

		$permission = true;
		foreach ($recordIds as $recordId) {
			if (\App\Modules\Users\Models\Privileges::isPermitted($moduleName, 'Delete', $recordId)) {
				$recordModel = \App\Modules\Vtiger\Models\Record::getInstanceById($recordId, $moduleName); // fixme: not 100% sure thats whats expected
				$recordModel->delete_rel($recordId);
				$recordModel->delete();
			} else {
				$permission = false;
			}
		}

		if (!$permission) {
			throw new \Exception\AppException(\App\Runtime\Vtiger_Language_Handler::translate('LBL_PERMISSION_DENIED'));
		}

		$cvId = $request->get('viewname');
		$response = new \App\Http\Vtiger_Response();
		$response->setResult(['viewname' => $cvId, 'module' => $moduleName]);
		$response->emit();
	}
}
