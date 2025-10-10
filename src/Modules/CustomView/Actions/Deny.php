<?php

namespace FreeCRM\Modules\CustomView\Actions;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

class Deny extends \FreeCRM\Runtime\Vtiger_Action_Controller
{

	public function process(\FreeCRM\Http\Vtiger_Request $request)
	{
		$currentUser = \FreeCRM\Modules\Users\Models\Record::getCurrentUserModel();
		$customViewModel = \FreeCRM\Modules\CustomView\Models\Record::getInstanceById($request->get('record'));
		$moduleModel = $customViewModel->getModule();
		if ($currentUser->isAdminUser()) {
			$customViewModel->deny();
		}

		$listViewUrl = $moduleModel->getListViewUrl();
		header("Location: $listViewUrl");
	}

	public function validateRequest(\FreeCRM\Http\Vtiger_Request $request)
	{
		$request->validateWriteAccess();
	}
}
