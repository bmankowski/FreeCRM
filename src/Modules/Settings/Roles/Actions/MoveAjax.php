<?php

namespace App\Modules\Settings\Roles\Actions;


/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

class MoveAjax extends \App\Modules\Settings\Base\Actions\Basic
{

	public function preProcess(\App\Http\Vtiger_Request $request, $display = true)
	{
		return;
	}

	public function postProcess(\App\Http\Vtiger_Request $request)
	{
		return;
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$recordId = $request->get('record');
		$parentRoleId = $request->get('parent_roleid');

		$parentRole = \App\Modules\Settings\Roles\Models\Record::getInstanceById($parentRoleId);
		$recordModel = \App\Modules\Settings\Roles\Models\Record::getInstanceById($recordId);

		$response = new \App\Http\Vtiger_Response();
		$response->setEmitType(\App\Http\Vtiger_Response::$EMIT_JSON);
		try {
			$recordModel->moveTo($parentRole);
		} catch (\App\Exceptions\AppException $e) {
			$response->setError('Move Role Failed');
		}
		$response->emit();
	}
}
