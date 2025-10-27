<?php
/* {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} */


namespace App\Modules\OSSPasswords\Actions;

class CheckPass extends \App\Base\Controllers\BaseActionController
{

	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		$userPrivilegesModel = \App\Modules\Users\Models\Privileges::getCurrentUserPrivilegesModel();
		$permission = $userPrivilegesModel->hasModulePermission($request->getModule());

		if (!$permission) {
			throw new \App\Exceptions\NoPermitted('LBL_PERMISSION_DENIED');
		}
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$password = $request->get('password');

		$recordModel = \App\Modules\Base\Models\Record::getCleanInstance($moduleName);

		$passOK = $recordModel->checkPassword($password);

		if ($passOK['error'] === true) {
			$result = array('success' => false, 'message' => $passOK['message']);
		} else {
			$result = array('success' => true, 'message' => '');
		}

		$response = new \App\Http\Vtiger_Response();
		$response->setResult($result);
		$response->emit();
	}
}
