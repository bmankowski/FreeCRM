<?php

namespace App\Modules\Users\Actions;

class CheckUserEmail extends \App\Runtime\Vtiger_Action_Controller
{

	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		$currentUser = \App\Modules\Users\Models\Record::getCurrentUserModel();
		if (!$currentUser->isAdminUser() && $currentUser->getId() != $request->get('cUser')) {
			throw new \Exception\NoPermitted('LBL_PERMISSION_DENIED');
		}
	}

	public function process(\App\Http\Vtiger_Request $request)
	{

		$moduleModel = \App\Modules\Vtiger\Models\Module::getInstance('Users');
		$output = !$moduleModel->checkMailExist($request->get('email'), $request->get('cUser'));

		$response = new \App\Http\Vtiger_Response();
		$response->setResult($output);
		$response->emit();
	}
}
