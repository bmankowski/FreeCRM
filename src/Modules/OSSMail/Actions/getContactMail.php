<?php
/* {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} */


namespace App\Modules\OSSMail\Actions;

class getContactMail extends \App\Runtime\Vtiger_Action_Controller
{

	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$currentUserPriviligesModel = \App\Modules\Users\Models\Privileges::getCurrentUserPrivilegesModel();
		if (!$currentUserPriviligesModel->hasModulePermission($moduleName)) {
			throw new \Exception\NoPermitted('LBL_PERMISSION_DENIED');
		}
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$ids = $request->get('ids');
		$mod = $request->get('mod');
		$emailFields = [];
		$searchList = \App\Modules\OSSMailScanner\Models\Record::getEmailSearch($mod);
		$recordModel = \App\Modules\Vtiger\Models\Record::getInstanceById($ids, $mod);
		$name = $recordModel->getName();
		foreach ($searchList as &$emailField) {
			$email = $recordModel->get($emailField['fieldname']);
			if ($email != '') {
				$fieldlabel = \App\Runtime\Vtiger_Language_Handler::translate($emailField['fieldlabel'], $emailField['name']);
				$emailFields[] = array('name' => $name, 'fieldlabel' => $fieldlabel, 'email' => $email);
			}
		}
		$response = new \App\Http\Vtiger_Response();
		$response->setResult($emailFields);
		$response->emit();
	}
}
