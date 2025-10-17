<?php
/* {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} */


namespace FreeCRM\Modules\OSSEmployees\Actions;

class GetHoliday extends \FreeCRM\Runtime\Vtiger_Action_Controller
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

		$id = $request->get('id');
		$year = $request->get('year');

		$sourceData = array();

		$recordModel = \FreeCRM\Modules\Vtiger\Models\Record::getCleanInstance($moduleName);

		$holiday['workDay'] = $recordModel->getHoliday($id, $year);
		$holiday['entitlement'] = $recordModel->getHolidaysEntitlement($id, $year);

		if (!$holiday) {
			$result = array('success' => false, 'message' => \FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_FAILED_TO_IMPORT_INFO', $moduleName));
		} else {
			$result = array('success' => true, 'holiday' => $holiday);
		}

		$response = new \FreeCRM\Http\Vtiger_Response();
		$response->setResult($result);
		$response->emit();
	}
}
