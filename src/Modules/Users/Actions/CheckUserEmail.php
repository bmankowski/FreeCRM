<?php

namespace FreeCRM\Modules\Users\Actions;

class CheckUserEmail extends \FreeCRM\Runtime\Vtiger_Action_Controller
{

	public function checkPermission(\FreeCRM\Http\Vtiger_Request $request)
	{
		$currentUser = Users_Record_Model::getCurrentUserModel();
		if (!$currentUser->isAdminUser() && $currentUser->getId() != $request->get('cUser')) {
			throw new \Exception\NoPermitted('LBL_PERMISSION_DENIED');
		}
	}

	public function process(Vtiger_Request $request)
	{

		$moduleModel = Vtiger_Module_Model::getInstance('Users');
		$output = !$moduleModel->checkMailExist($request->get('email'), $request->get('cUser'));

		$response = new Vtiger_Response();
		$response->setResult($output);
		$response->emit();
	}
}
