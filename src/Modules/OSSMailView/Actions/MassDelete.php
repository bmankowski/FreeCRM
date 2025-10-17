<?php

namespace FreeCRM\Modules\OSSMailView\Actions;

/**
 * Mass delete action class
 * @package YetiForce.Action
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */
class MassDelete extends \FreeCRM\Runtime\Vtiger_Action_Controller
{

	public function checkPermission(\FreeCRM\Http\Vtiger_Request $request)
	{
		$currentUserPriviligesModel = \FreeCRM\Modules\Users\Models\Privileges::getCurrentUserPrivilegesModel();
		if (!$currentUserPriviligesModel->hasModulePermission($request->getModule())) {
			throw new \Exception\NoPermitted('LBL_PERMISSION_DENIED');
		}
	}

	public function preProcess(\FreeCRM\Http\Vtiger_Request $request)
	{
		return true;
	}

	public function postProcess(\FreeCRM\Http\Vtiger_Request $request)
	{
		return true;
	}

	public function process(\FreeCRM\Http\Vtiger_Request $request)
	{

		$moduleName = $request->getModule();
		$recordModel = new OSSMailView_Record_Model();
		$recordModel->setModule($moduleName);

		$recordIds = $this->getRecordsListFromRequest($request);

		$permission = true;
		foreach ($recordIds as $recordId) {
			if (\FreeCRM\Modules\Users\Models\Privileges::isPermitted($moduleName, 'Delete', $recordId)) {
				$recordModel = \FreeCRM\Modules\Vtiger\Models\Record::getInstanceById($recordId, $moduleName); // fixme: not 100% sure thats whats expected
				$recordModel->delete_rel($recordId);
				$recordModel->delete();
			} else {
				$permission = false;
			}
		}

		if (!$permission) {
			throw new \Exception\AppException(\FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_PERMISSION_DENIED'));
		}

		$cvId = $request->get('viewname');
		$response = new \FreeCRM\Http\Vtiger_Response();
		$response->setResult(['viewname' => $cvId, 'module' => $moduleName]);
		$response->emit();
	}
}
