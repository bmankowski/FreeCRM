<?php
/* {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} */


namespace FreeCRM\Modules\OSSPasswords\Actions;

class GetPass extends \FreeCRM\Runtime\Vtiger_Action_Controller
{

	public function checkPermission(\FreeCRM\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$userPrivilegesModel = \FreeCRM\Modules\Users\Models\Privileges::getCurrentUserPrivilegesModel();
		$permission = $userPrivilegesModel->hasModulePermission($moduleName);
		if (!$permission) {
			throw new \Exception\NoPermitted('LBL_PERMISSION_DENIED');
		}

		$record = $request->get('record');
		if ($record) {
			$recordPermission = \FreeCRM\Modules\Users\Models\Privileges::isPermitted($moduleName, 'DetailView', $record);
			if (!$recordPermission) {
				throw new \Exception\NoPermittedToRecord('LBL_NO_PERMISSIONS_FOR_THE_RECORD');
			}
		}
	}

	public function process(\FreeCRM\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$record = $request->get('record');

		if ($record) {
			$recordModel = \FreeCRM\Modules\Vtiger\Models\Record::getInstanceById($record, $moduleName);
		} else {
			$recordModel = \FreeCRM\Modules\Vtiger\Models\Record::getCleanInstance($moduleName);
		}

		$pass = $recordModel->getPassword($record);
		if ($pass === false) {
			$result = array('success' => false);
		} else {
			$result = array('success' => true, 'password' => $pass);
		}

		$response = new \FreeCRM\Http\Vtiger_Response();
		$response->setResult($result);
		$response->emit();
	}
}
