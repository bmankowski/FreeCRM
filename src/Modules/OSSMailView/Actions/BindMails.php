<?php

namespace FreeCRM\Modules\OSSMailView\Actions;

/**
 * Bind mails action class
 * @package YetiForce.Action
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */
class BindMails extends \FreeCRM\Runtime\Vtiger_Action_Controller
{

	public function checkPermission(\FreeCRM\Http\Vtiger_Request $request)
	{
		$currentUserPriviligesModel = \FreeCRM\Modules\Users\Models\Privileges::getCurrentUserPrivilegesModel();
		if (!$currentUserPriviligesModel->hasModulePermission($request->getModule())) {
			throw new \Exception\NoPermitted('LBL_PERMISSION_DENIED');
		}
	}

	public function process(\FreeCRM\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$recordModel = \FreeCRM\Modules\Vtiger\Models\Record::getCleanInstance($moduleName);
		$recordsList = Vtiger_Mass_Action::getRecordsListFromRequest($request);
		$recordModel->bindSelectedRecords($recordsList);
		$response = new \FreeCRM\Http\Vtiger_Response();
		$response->setResult(vtranslate('LBL_BindMailsOK', $moduleName));
		$response->emit();
	}
}
