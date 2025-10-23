<?php

namespace App\Modules\Settings\PBXManager\Actions;


/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

class SaveAjax extends \App\Modules\Vtiger\Actions\Save
{

	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		$currentUserModel = \App\Modules\Users\Models\Record::getCurrentUserModel();
		if (!$currentUserModel->isAdminUser()) {
			throw new \Exception\NoPermittedForAdmin('LBL_PERMISSION_DENIED');
		}
	}

	// To save Mapping of user from mapping popup
	public function process(\App\Http\Vtiger_Request $request)
	{
		$id = $request->get('id');
		$qualifiedModuleName = 'PBXManager';

		$recordModel = Settings_PBXManager_Record_Model::getCleanInstance();
		$recordModel->set('gateway', $qualifiedModuleName);
		if ($id) {
			$recordModel->set('id', $id);
		}

		foreach (PBXManager_PBXManager_Connector::getSettingsParameters() as $field => $type) {
			$recordModel->set($field, $request->get($field));
		}

		$response = new \App\Http\Vtiger_Response();
		try {
			$recordModel->save();
			$response->setResult(true);
		} catch (Exception $e) {
			$response->setError($e->getMessage());
		}
		$response->emit();
	}
}
