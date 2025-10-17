<?php
/* {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} */


namespace App\Modules\OSSEmployees\Actions;

class UniqueUser extends \App\Runtime\Vtiger_Action_Controller
{

	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$userPrivilegesModel = \App\Modules\Users\Models\Privileges::getCurrentUserPrivilegesModel();
		$permission = $userPrivilegesModel->hasModulePermission($moduleName);

		if (!$permission) {
			throw new \Exception\NoPermitted('LBL_PERMISSION_DENIED');
		}
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$adb = \App\database\PearDatabase::getInstance();
		$moduleName = $request->getModule();

		$userId = $request->get('userId');

		$recordModel = \App\Modules\Vtiger\Models\Record::getCleanInstance($moduleName);

		$userExists = $recordModel->checkUser($userId);

		if (!$userExists) {
			$result = array('success' => false, 'message' => \App\Runtime\Vtiger_Language_Handler::translate('LBL_USER_EXISTS', $moduleName));
		} else {
			$result = array('success' => true);
		}

		$response = new \App\Http\Vtiger_Response();
		$response->setResult($result);
		$response->emit();
	}
}
