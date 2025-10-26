<?php

namespace App\Modules\Portal\Actions;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * *********************************************************************************** */

class SaveAjax extends \App\Modules\Base\Actions\Save
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
		$module = $request->getModule();
		$recordId = $request->get('record');
		$bookmarkName = $request->get('bookmarkName');
		$bookmarkUrl = $request->get('bookmarkUrl');

		\App\Modules\Portal\Models\Module::savePortalRecord($recordId, $bookmarkName, $bookmarkUrl);

		$response = new \App\Http\Vtiger_Response();
		$result = array('message' => \App\Runtime\Vtiger_Language_Handler::translate('LBL_BOOKMARK_SAVED_SUCCESSFULLY', $module));
		$response->setResult($result);
		$response->emit();
	}
}
