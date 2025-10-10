<?php

namespace FreeCRM\Modules\Users\Actions;

class CheckUserEmail extends \FreeCRM\Runtime\Vtiger_Action_Controller
{

	public function checkPermission(\FreeCRM\Http\Vtiger_Request $request)
	{
		$currentUser = \FreeCRM\Modules\Users\Models\Record::getCurrentUserModel();
		if (!$currentUser->isAdminUser() && $currentUser->getId() != $request->get('cUser')) {
			throw new \Exception\NoPermitted('LBL_PERMISSION_DENIED');
		}
	}

	public function process(\FreeCRM\Http\Vtiger_Request $request)
	{

		$moduleModel = \FreeCRM\Modules\Vtiger\Models\Module::getInstance('Users');
		$output = !$moduleModel->checkMailExist($request->get('email'), $request->get('cUser'));

		$response = new \FreeCRM\Http\Vtiger_Response();
		$response->setResult($output);
		$response->emit();
	}
}
