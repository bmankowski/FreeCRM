<?php

namespace App\Modules\Rss\Actions;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * *********************************************************************************** */

class Save extends \App\Base\Controllers\BaseActionController
{

	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		$currentUserModel = \App\Modules\Users\Models\Privileges::getCurrentUserPrivilegesModel();
		if (!$currentUserModel->hasModulePermission($request->getModule())) {
			throw new \App\Exceptions\NoPermittedToRecord('LBL_PERMISSION_DENIED');
		}
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$response = new \App\Http\Vtiger_Response();
		$moduleName = $request->getModule();
		$url = $request->get('feedurl');
		$recordModel = \App\Modules\Rss\Models\Record::getCleanInstance($moduleName);
		$result = $recordModel->validateRssUrl($url);
		if ($result) {
			$recordModel->saveRecord($url);
			$response->setResult(['success' => true, 'message' => \App\Runtime\Vtiger_Language_Handler::translate('JS_RSS_SUCCESSFULLY_SAVED', $moduleName), 'id' => $recordModel->getId()]);
		} else {
			$response->setResult(['success' => false, 'message' => \App\Runtime\Vtiger_Language_Handler::translate('JS_INVALID_RSS_URL', $moduleName)]);
		}

		$response->emit();
	}
}
