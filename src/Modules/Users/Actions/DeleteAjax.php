<?php

namespace FreeCRM\Modules\Users\Actions;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * *********************************************************************************** */
require_once ROOT_DIRECTORY . '/src/Webservices/Custom/DeleteUser.php';

class DeleteAjax extends \FreeCRM\Runtime\Vtiger_Action_Controller
{

	public function checkPermission(\FreeCRM\Http\Vtiger_Request $request)
	{
		$currentUserModel = \FreeCRM\Modules\Users\Models\Record::getCurrentUserModel();
		if (!$currentUserModel->isAdminUser()) {
			throw new \Exception\NoPermitted('LBL_PERMISSION_DENIED');
		}
	}

	public function process(\FreeCRM\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$ownerId = $request->get('userid');
		$newOwnerId = $request->get('transfer_user_id');
		if ($request->get('mode') === 'permanent') {
			\FreeCRM\Modules\Users\Models\Record::deleteUserPermanently($ownerId, $newOwnerId);
		} else {
			$userId = vtws_getWebserviceEntityId($moduleName, $ownerId);
			$transformUserId = vtws_getWebserviceEntityId($moduleName, $newOwnerId);

			$userModel = \FreeCRM\Modules\Users\Models\Record::getCurrentUserModel();

			vtws_deleteUser($userId, $transformUserId, $userModel);

			if ($request->get('permanent') === '1')
				\FreeCRM\Modules\Users\Models\Record::deleteUserPermanently($ownerId, $newOwnerId);
		}
		$userModuleModel = \FreeCRM\Modules\Users\Models\Module::getInstance($moduleName);
		$listViewUrl = $userModuleModel->getListViewUrl();
		$response = new \FreeCRM\Http\Vtiger_Response();
		$response->setResult(['message' => \FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_USER_DELETED_SUCCESSFULLY', $moduleName), 'listViewUrl' => $listViewUrl]);
		$response->emit();
	}
}
