<?php

namespace App\Modules\Users\Views;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */


use App\Http\Vtiger_Request;
class TransferOwner  extends \App\Modules\Vtiger\Views\Index
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
		$userid = $request->get('record');

		$userRecordModel = $request->getUser();
		$viewer = $this->getViewer($request);
		$usersList = $userRecordModel->getActiveAdminUsers(true);

		if (array_key_exists($userid, $usersList)) {
			unset($usersList[$userid]);
		}

		$viewer->assign('USERID', $userid);
		$viewer->assign('TRANSFER_USER_NAME', $userRecordModel->getName());
		$viewer->assign('USER_LIST', $usersList);
		$viewer->view('TransferOwner.tpl', $moduleName);
	}
}
