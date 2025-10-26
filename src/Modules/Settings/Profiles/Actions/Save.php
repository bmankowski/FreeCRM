<?php

namespace App\Modules\Settings\Profiles\Actions;


/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

class Save extends \App\Runtime\BaseActionController
{

	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		$currentUser = $request->getUser();
		if (!$currentUser->isAdminUser()) {
			throw new \Exception\AppException('LBL_PERMISSION_DENIED');
		}
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$recordId = $request->get('record');

		if (!empty($recordId)) {
			$recordModel = \App\Modules\Settings\Profiles\Model\Record::getInstanceById($recordId);
		} else {
			$recordModel = new \App\Modules\Settings\Profiles\Model\Record();
		}
		if ($recordModel) {
			$recordModel->set('profilename', $request->get('profilename'));
			$recordModel->set('description', $request->get('description'));
			$recordModel->set('viewall', $request->get('viewall'));
			$recordModel->set('editall', $request->get('editall'));
			$recordModel->set('profile_permissions', $request->get('permissions'));
			$recordModel->save();
		}

		$redirectUrl = $recordModel->getDetailViewUrl();
		header("Location: $redirectUrl");
	}

	public function validateRequest(\App\Http\Vtiger_Request $request)
	{
		$request->validateWriteAccess();
	}
}
