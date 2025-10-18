<?php
/* {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} */


namespace App\Modules\OSSEmployees\Actions;

class GetHoliday extends \App\Runtime\Vtiger_Action_Controller
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
		$adb = \App\Database\database\PearDatabase::getInstance();
		$moduleName = $request->getModule();

		$id = $request->get('id');
		$year = $request->get('year');

		$sourceData = array();

		$recordModel = \App\Modules\Vtiger\Models\Record::getCleanInstance($moduleName);

		$holiday['workDay'] = $recordModel->getHoliday($id, $year);
		$holiday['entitlement'] = $recordModel->getHolidaysEntitlement($id, $year);

		if (!$holiday) {
			$result = array('success' => false, 'message' => \App\Runtime\Vtiger_Language_Handler::translate('LBL_FAILED_TO_IMPORT_INFO', $moduleName));
		} else {
			$result = array('success' => true, 'holiday' => $holiday);
		}

		$response = new \App\Http\Vtiger_Response();
		$response->setResult($result);
		$response->emit();
	}
}
