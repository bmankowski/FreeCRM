<?php
/* {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} */


namespace FreeCRM\Modules\OSSPasswords\Actions;

class CheckPass extends \FreeCRM\Runtime\Vtiger_Action_Controller
{

	public function checkPermission(\FreeCRM\Http\Vtiger_Request $request)
	{
		$userPrivilegesModel = \FreeCRM\Modules\Users\Models\Privileges::getCurrentUserPrivilegesModel();
		$permission = $userPrivilegesModel->hasModulePermission($request->getModule());

		if (!$permission) {
			throw new \Exception\NoPermitted('LBL_PERMISSION_DENIED');
		}
	}

	public function process(\FreeCRM\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$password = $request->get('password');

		$recordModel = \FreeCRM\Modules\Vtiger\Models\Record::getCleanInstance($moduleName);

		$passOK = $recordModel->checkPassword($password);

		if ($passOK['error'] === true) {
			$result = array('success' => false, 'message' => $passOK['message']);
		} else {
			$result = array('success' => true, 'message' => '');
		}

		$response = new \FreeCRM\Http\Vtiger_Response();
		$response->setResult($result);
		$response->emit();
	}
}
