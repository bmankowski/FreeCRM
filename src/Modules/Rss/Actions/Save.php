<?php

namespace FreeCRM\Modules\Rss\Actions;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * *********************************************************************************** */

class Save extends \FreeCRM\Runtime\Vtiger_Action_Controller
{

	public function checkPermission(\FreeCRM\Http\Vtiger_Request $request)
	{
		$currentUserModel = \FreeCRM\Modules\Users\Models\Privileges::getCurrentUserPrivilegesModel();
		if (!$currentUserModel->hasModulePermission($request->getModule())) {
			throw new \Exception\NoPermittedToRecord('LBL_PERMISSION_DENIED');
		}
	}

	public function process(\FreeCRM\Http\Vtiger_Request $request)
	{
		$response = new \FreeCRM\Http\Vtiger_Response();
		$moduleName = $request->getModule();
		$url = $request->get('feedurl');
		$recordModel = \FreeCRM\Modules\Rss\Models\Record::getCleanInstance($moduleName);
		$result = $recordModel->validateRssUrl($url);
		if ($result) {
			$recordModel->saveRecord($url);
			$response->setResult(['success' => true, 'message' => \FreeCRM\Runtime\Vtiger_Language_Handler::translate('JS_RSS_SUCCESSFULLY_SAVED', $moduleName), 'id' => $recordModel->getId()]);
		} else {
			$response->setResult(['success' => false, 'message' => \FreeCRM\Runtime\Vtiger_Language_Handler::translate('JS_INVALID_RSS_URL', $moduleName)]);
		}

		$response->emit();
	}
}
