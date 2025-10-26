<?php
/* {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} */


namespace App\Modules\OSSMail\Actions;

class checkMails extends \App\Runtime\BaseActionController
{

	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$userPrivilegesModel = \App\Modules\Users\Models\Privileges::getCurrentUserPrivilegesModel();
		$permission = $userPrivilegesModel->hasModulePermission($moduleName);

		if (!$permission) {
			throw new \App\Exceptions\NoPermitted('LBL_PERMISSION_DENIED');
		}
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$users = $request->get('users');
		$output = [];
		if (count($users) > 0) {
			\App\Modules\OSSMail\Models\Record::updateMailBoxmsgInfo($users);
			$output = \App\Modules\OSSMail\Models\Record::getMailBoxmsgInfo($users);
		}
		$response = new \App\Http\Vtiger_Response();
		$response->setResult($output);
		$response->emit();
	}
}
