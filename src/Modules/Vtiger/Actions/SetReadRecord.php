F<?php

namespace App\Modules\Vtiger\Actions;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * *********************************************************************************** */

class SetReadRecord extends \App\Runtime\BaseActionController
{

	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		$currentUserPriviligesModel = \App\Modules\Users\Models\Privileges::getCurrentUserPrivilegesModel();
		if (!$currentUserPriviligesModel->hasModuleActionPermission($request->getModule(), 'ReadRecord')) {
			throw new \Exception\NoPermitted('LBL_PERMISSION_DENIED');
		}
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$recordId = $request->get('record');

		$recordModel = $this->saveRecord($request);

		$cvId = $request->get('viewname');
		$response = new \App\Http\Vtiger_Response();
		$response->setResult(array('viewname' => $cvId, 'module' => $moduleName));
		$response->emit();
	}
}
