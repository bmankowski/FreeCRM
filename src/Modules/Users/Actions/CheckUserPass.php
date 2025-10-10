<?php

namespace FreeCRM\Modules\Users\Actions;
use FreeCRM\Modules\Settings\PasswordModels\Record as Settings_Password_Record_Model;

class CheckUserPass extends \FreeCRM\Runtime\Vtiger_Action_Controller
{

	public function checkPermission(\FreeCRM\Http\Vtiger_Request $request)
	{
		$currentUser = \FreeCRM\Modules\Users\Models\Record::getCurrentUserModel();
		if (!$currentUser->isAdminUser()) {
			throw new \Exception\NoPermitted('LBL_PERMISSION_DENIED');
		}
	}

	public function process(\FreeCRM\Http\Vtiger_Request $request)
	{

		$response = new \FreeCRM\Http\Vtiger_Response();
		$response->setResult(Settings_Password_Record_Model::checkPassword($request->get('pass')));
		$response->emit();
	}
}
