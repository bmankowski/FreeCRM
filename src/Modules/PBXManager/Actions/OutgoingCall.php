<?php

namespace App\Modules\PBXManager\Actions;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * *********************************************************************************** */

class OutgoingCall extends \App\Runtime\BaseActionController
{

	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		$userPrivilegesModel = \App\Modules\Users\Models\Privileges::getCurrentUserPrivilegesModel();
		$permission = $userPrivilegesModel->hasModulePermission($request->getModule());

		if (!$permission) {
			throw new \Exception\NoPermitted('LBL_PERMISSION_DENIED');
		}
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$serverModel = PBXManager_Server_Model::getInstance();
		$gateway = $serverModel->get("gateway");
		$response = new \App\Http\Vtiger_Response();
		$user = $request->getUser();
		$userNumber = $user->phone_crm_extension;

		if ($gateway && $userNumber) {
			try {
				$number = $request->get('number');
				$recordId = $request->get('record');
				$connector = $serverModel->getConnector();
				$result = $connector->call($number, $recordId);
				$response->setResult($result);
			} catch (Exception $e) {
				throw new Exception($e);
			}
		} else {
			$response->setResult(false);
		}
		$response->emit();
	}
}
