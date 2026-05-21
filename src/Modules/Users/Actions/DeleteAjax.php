<?php

namespace App\Modules\Users\Actions;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * *********************************************************************************** */

class DeleteAjax extends \App\Base\Controllers\BaseActionController
{

	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		$currentUserModel = $request->getUser();
		if (!$currentUserModel->isAdminUser()) {
			throw new \App\Exceptions\NoPermitted('LBL_PERMISSION_DENIED');
		}
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$ownerId = $request->get('userid');
		$newOwnerId = $request->get('transfer_user_id');
		if ($request->get('mode') === 'permanent') {
			\App\Modules\Users\Models\Record::deleteUserPermanently($ownerId, $newOwnerId);
		} else {
			$userObj = new \App\Modules\Users\Users();
			$userObj->transformOwnerShipAndDelete((int) $ownerId, (int) $newOwnerId);

			if ($request->get('permanent') === '1') {
				\App\Modules\Users\Models\Record::deleteUserPermanently($ownerId, $newOwnerId);
			}
		}
		$userModuleModel = \App\Modules\Users\Models\Module::getInstance($moduleName);
		$listViewUrl = $userModuleModel->getListViewUrl();
		$response = new \App\Http\Vtiger_Response();
		$response->setResult(['message' => \App\Runtime\Vtiger_Language_Handler::translate('LBL_USER_DELETED_SUCCESSFULLY', $moduleName), 'listViewUrl' => $listViewUrl]);
		$response->emit();
	}
}
