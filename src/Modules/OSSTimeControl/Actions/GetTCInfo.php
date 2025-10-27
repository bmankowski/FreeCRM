<?php
/* {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} */


namespace App\Modules\OSSTimeControl\Actions;

class GetTCInfo extends \App\Base\Controllers\BaseActionController
{

	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		$userPrivilegesModel = \App\Modules\Users\Models\Privileges::getCurrentUserPrivilegesModel();
		$permission = $userPrivilegesModel->hasModulePermission($request->getModule());
		if (!$permission) {
			throw new \App\Exceptions\NoPermitted('LBL_PERMISSION_DENIED');
		}

		$srecord = $request->get('id');
		$smodule = $request->get('sourceModule');

		$recordPermission = \App\Modules\Users\Models\Privileges::isPermitted($smodule, 'DetailView', $srecord);
		if (!$recordPermission) {
			throw new \App\Exceptions\NoPermittedToRecord('LBL_NO_PERMISSIONS_FOR_THE_RECORD');
		}
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$adb = \App\Database\PearDatabase::getInstance();
		$moduleName = $request->getModule();

		$id = $request->get('id');
		$sourceModule = $request->get('sourceModule');

		$sourceData = array();

		if (\App\Utils\Utils::isRecordExists($id)) {
			$record = \App\Modules\Base\Models\Record::getInstanceById($id, $sourceModule);
			$entity = $record->getEntity();
			$sourceData = $entity->column_fields;
			if ($sourceModule == 'HelpDesk') {
				$sourceData['contact_label'] = \vtlib\Functions::getCRMRecordLabel($sourceData['contact_id']);
				if (\vtlib\Functions::getCRMRecordType($sourceData['parent_id']) != 'Accounts')
					unset($sourceData['parent_id']);
				else
					$sourceData['account_label'] = \vtlib\Functions::getCRMRecordLabel($sourceData['parent_id']);
			} else if ($sourceModule == 'Project') {
				$query = sprintf("select * from vtiger_account where accountid = %s", $sourceData['linktoaccountscontacts']);
				$ifExist = $adb->query($query, true, "Błąd podczas pobierania danych z vtiger_crmentityrel");
				if ($adb->num_rows($ifExist) > 0)
					$sourceData['account_label'] = \vtlib\Functions::getCRMRecordLabel($sourceData['linktoaccountscontacts']);
				else
					$sourceData['contact_label'] = \vtlib\Functions::getCRMRecordLabel($sourceData['linktoaccountscontacts']);
			}
		}

		if ($sourceData === false) {
			$result = array('success' => false, 'message' => \App\Runtime\Vtiger_Language_Handler::translate('LBL_FAILED_TO_IMPORT_INFO', $moduleName));
		} else {
			$result = array('success' => true, 'sourceData' => $sourceData);
		}

		$response = new \App\Http\Vtiger_Response();
		$response->setResult($result);
		$response->emit();
	}
}
