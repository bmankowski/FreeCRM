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

Class \App\Modules\Users\Views\EditAjax extends \App\Modules\Vtiger\Views\IndexAjax
{

	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		$currentUserModel = \App\Modules\Users\Models\Record::getCurrentUserModel();
		$record = $request->get('record');
		if ($currentUserModel->isAdminUser() === true || $currentUserModel->get('id') == $record) {
			return true;
		} else {
			throw new \Exception\AppException('LBL_PERMISSION_DENIED');
		}
	}

	public function __construct()
	{
		parent::__construct();
		$this->exposeMethod('changePassword');
		$this->exposeMethod('editPasswords');
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$mode = $request->get('mode');
		if (!empty($mode)) {
			$this->invokeExposedMethod($mode, $request);
			return;
		}
	}

	public function changePassword(\App\Http\Vtiger_Request $request)
	{
		$viewer = $this->getViewer($request);
		$moduleName = $request->get('module');
		$userId = $request->get('record');

		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('USERID', $userId);
		$viewer->assign('CURRENT_USER_MODEL', \App\Modules\Users\Models\Record::getCurrentUserModel());
		$viewer->view('ChangePassword.tpl', $moduleName);
	}

	public function editPasswords(\App\Http\Vtiger_Request $request)
	{
		$viewer = $this->getViewer($request);
		$moduleName = $request->get('module');
		$userIds = $request->get('userids');

		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('USERIDS', json_encode($userIds));
		$viewer->assign('CURRENT_USER_MODEL', \App\Modules\Users\Models\Record::getCurrentUserModel());
		$viewer->view('EditPasswords.tpl', $moduleName);
	}
}
