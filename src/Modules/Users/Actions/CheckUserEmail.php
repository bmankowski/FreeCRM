<?php

namespace App\Modules\Users\Actions;

class CheckUserEmail extends \App\Base\Controllers\BaseActionController
{

	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		$currentUser = $request->getUser();
		if (!$currentUser->isAdminUser() && $currentUser->getId() != $request->get('cUser')) {
			throw new \App\Exceptions\NoPermitted('LBL_PERMISSION_DENIED');
		}
	}

	public function process(\App\Http\Vtiger_Request $request)
	{

		$moduleModel = \App\Modules\Base\Models\Module::getInstance('Users');
		$output = !$moduleModel->checkMailExist($request->get('email'), $request->get('cUser'));

		$response = new \App\Http\Vtiger_Response();
		$response->setResult($output);
		$response->emit();
	}
}
