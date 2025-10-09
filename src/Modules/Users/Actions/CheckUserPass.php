<?php

namespace FreeCRM\Modules\Users\Actions;

class CheckUserPass extends \FreeCRM\Runtime\Vtiger_Action_Controller
{

	public function checkPermission(\FreeCRM\Http\Vtiger_Request $request)
	{
		$currentUser = Users_Record_Model::getCurrentUserModel();
		if (!$currentUser->isAdminUser()) {
			throw new \Exception\NoPermitted('LBL_PERMISSION_DENIED');
		}
	}

	public function process(Vtiger_Request $request)
	{

		$response = new Vtiger_Response();
		$response->setResult(Settings_Password_Record_Model::checkPassword($request->get('pass')));
		$response->emit();
	}
}
