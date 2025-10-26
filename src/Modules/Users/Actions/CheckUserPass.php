<?php

namespace App\Modules\Users\Actions;
use App\Modules\Settings\PasswordModels\Record;

class CheckUserPass extends \App\Runtime\BaseActionController
{

	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		$currentUser = $request->getUser();
		if (!$currentUser->isAdminUser()) {
			throw new \Exception\NoPermitted('LBL_PERMISSION_DENIED');
		}
	}

	public function process(\App\Http\Vtiger_Request $request)
	{

		$response = new \App\Http\Vtiger_Response();
		$response->setResult(\App\Modules\Settings\Password\Models\Record::checkPassword($request->get('pass')));
		$response->emit();
	}
}
