<?php
/* {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} */


namespace FreeCRM\Modules\OSSMail\Actions;

class checkMails extends \FreeCRM\Runtime\Vtiger_Action_Controller
{

	public function checkPermission(\FreeCRM\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$userPrivilegesModel = \FreeCRM\Modules\Users\Models\Privileges::getCurrentUserPrivilegesModel();
		$permission = $userPrivilegesModel->hasModulePermission($moduleName);

		if (!$permission) {
			throw new \Exception\NoPermitted('LBL_PERMISSION_DENIED');
		}
	}

	public function process(\FreeCRM\Http\Vtiger_Request $request)
	{
		$users = $request->get('users');
		$output = [];
		if (count($users) > 0) {
			\FreeCRM\Modules\OSSMail\Models\Record::updateMailBoxmsgInfo($users);
			$output = \FreeCRM\Modules\OSSMail\Models\Record::getMailBoxmsgInfo($users);
		}
		$response = new \FreeCRM\Http\Vtiger_Response();
		$response->setResult($output);
		$response->emit();
	}
}
