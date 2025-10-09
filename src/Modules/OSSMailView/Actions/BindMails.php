<?php

namespace FreeCRM\Modules\OSSMailView\Actions;

/**
 * Bind mails action class
 * @package YetiForce.Action
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */
class BindMails extends Action
{

	public function checkPermission(\FreeCRM\Http\Vtiger_Request $request)
	{
		$currentUserPriviligesModel = Users_Privileges_Model::getCurrentUserPrivilegesModel();
		if (!$currentUserPriviligesModel->hasModulePermission($request->getModule())) {
			throw new \Exception\NoPermitted('LBL_PERMISSION_DENIED');
		}
	}

	public function process(Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$recordModel = Vtiger_Record_Model::getCleanInstance($moduleName);
		$recordsList = Vtiger_Mass_Action::getRecordsListFromRequest($request);
		$recordModel->bindSelectedRecords($recordsList);
		$response = new Vtiger_Response();
		$response->setResult(vtranslate('LBL_BindMailsOK', $moduleName));
		$response->emit();
	}
}
