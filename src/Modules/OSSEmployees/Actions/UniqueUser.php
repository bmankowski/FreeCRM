<?php
/* {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} */


namespace FreeCRM\Modules\OSSEmployees\Actions;

class UniqueUser extends \FreeCRM\Runtime\Vtiger_Action_Controller
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
		$adb = \FreeCRM\database\PearDatabase::getInstance();
		$moduleName = $request->getModule();

		$userId = $request->get('userId');

		$recordModel = \FreeCRM\Modules\Vtiger\Models\Record::getCleanInstance($moduleName);

		$userExists = $recordModel->checkUser($userId);

		if (!$userExists) {
			$result = array('success' => false, 'message' => \FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_USER_EXISTS', $moduleName));
		} else {
			$result = array('success' => true);
		}

		$response = new \FreeCRM\Http\Vtiger_Response();
		$response->setResult($result);
		$response->emit();
	}
}
