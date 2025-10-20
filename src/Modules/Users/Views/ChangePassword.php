<?php

namespace App\Modules\Users\Views;

/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * ********************************************************************************** */


use App\Http\Vtiger_Request;
class ChangePassword  extends \App\Modules\Vtiger\Views\Index
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

	public function process(\App\Http\Vtiger_Request $request)
	{
		$currentUser = \App\Modules\Users\Models\Record::getCurrentUserModel();
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		$viewer->assign('MODULE', $moduleName);
		$this->preProcess($request);
		$viewer->view('ChangePasswordUser.tpl', $moduleName);
		$this->postProcess($request);
	}
}
