<?php
/* {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} */


namespace App\Modules\OSSPasswords\Actions;

class GetPass extends \App\Runtime\Vtiger_Action_Controller
{

	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$userPrivilegesModel = \App\Modules\Users\Models\Privileges::getCurrentUserPrivilegesModel();
		$permission = $userPrivilegesModel->hasModulePermission($moduleName);
		if (!$permission) {
			throw new \Exception\NoPermitted('LBL_PERMISSION_DENIED');
		}

		$record = $request->get('record');
		if ($record) {
			$recordPermission = \App\Modules\Users\Models\Privileges::isPermitted($moduleName, 'DetailView', $record);
			if (!$recordPermission) {
				throw new \Exception\NoPermittedToRecord('LBL_NO_PERMISSIONS_FOR_THE_RECORD');
			}
		}
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$record = $request->get('record');

		if ($record) {
			$recordModel = \App\Modules\Vtiger\Models\Record::getInstanceById($record, $moduleName);
		} else {
			$recordModel = \App\Modules\Vtiger\Models\Record::getCleanInstance($moduleName);
		}

		$pass = $recordModel->getPassword($record);
		if ($pass === false) {
			$result = array('success' => false);
		} else {
			$result = array('success' => true, 'password' => $pass);
		}

		$response = new \App\Http\Vtiger_Response();
		$response->setResult($result);
		$response->emit();
	}
}
