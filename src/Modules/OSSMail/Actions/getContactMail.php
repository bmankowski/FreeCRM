<?php
/* {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} */


namespace FreeCRM\Modules\OSSMail\Actions;

class getContactMail extends \FreeCRM\Runtime\Vtiger_Action_Controller
{

	public function checkPermission(\FreeCRM\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$currentUserPriviligesModel = \FreeCRM\Modules\Users\Models\Privileges::getCurrentUserPrivilegesModel();
		if (!$currentUserPriviligesModel->hasModulePermission($moduleName)) {
			throw new \Exception\NoPermitted('LBL_PERMISSION_DENIED');
		}
	}

	public function process(\FreeCRM\Http\Vtiger_Request $request)
	{
		$ids = $request->get('ids');
		$mod = $request->get('mod');
		$emailFields = [];
		$searchList = \FreeCRM\Modules\OSSMailScanner\Models\Record::getEmailSearch($mod);
		$recordModel = \FreeCRM\Modules\Vtiger\Models\Record::getInstanceById($ids, $mod);
		$name = $recordModel->getName();
		foreach ($searchList as &$emailField) {
			$email = $recordModel->get($emailField['fieldname']);
			if ($email != '') {
				$fieldlabel = \FreeCRM\Runtime\Vtiger_Language_Handler::translate($emailField['fieldlabel'], $emailField['name']);
				$emailFields[] = array('name' => $name, 'fieldlabel' => $fieldlabel, 'email' => $email);
			}
		}
		$response = new \FreeCRM\Http\Vtiger_Response();
		$response->setResult($emailFields);
		$response->emit();
	}
}
