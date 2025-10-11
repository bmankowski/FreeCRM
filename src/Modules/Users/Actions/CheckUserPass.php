<?php

namespace FreeCRM\Modules\Users\Actions;
use FreeCRM\Modules\Settings\PasswordModels\Record;

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
		$response->setResult(\FreeCRM\Modules\Settings\Password\Models\Record::checkPassword($request->get('pass')));
		$response->emit();
	}
}
