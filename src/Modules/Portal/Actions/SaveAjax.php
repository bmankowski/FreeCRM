<?php

namespace FreeCRM\Modules\Portal\Actions;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * *********************************************************************************** */

class SaveAjax extends \FreeCRM\Modules\Vtiger\Actions\Save
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
		$module = $request->getModule();
		$recordId = $request->get('record');
		$bookmarkName = $request->get('bookmarkName');
		$bookmarkUrl = $request->get('bookmarkUrl');

		\FreeCRM\Modules\Portal\Models\Module::savePortalRecord($recordId, $bookmarkName, $bookmarkUrl);

		$response = new \FreeCRM\Http\Vtiger_Response();
		$result = array('message' => \FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_BOOKMARK_SAVED_SUCCESSFULLY', $module));
		$response->setResult($result);
		$response->emit();
	}
}
