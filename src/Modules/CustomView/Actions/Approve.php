<?php

namespace App\Modules\CustomView\Actions;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

class Approve extends \App\Runtime\BaseActionController
{

	public function process(\App\Http\Vtiger_Request $request)
	{
		$currentUser = $request->getUser();
		if ($currentUser->isAdminUser()) {
			$customViewModel = \App\Modules\CustomView\Models\Record::getInstanceById($request->get('record'));
			$moduleModel = $customViewModel->getModule();

			$customViewModel->approve();
		}
		$listViewUrl = $moduleModel->getListViewUrl();
		header("Location: $listViewUrl");
	}

	public function validateRequest(\App\Http\Vtiger_Request $request)
	{
		$request->validateWriteAccess();
	}
}
