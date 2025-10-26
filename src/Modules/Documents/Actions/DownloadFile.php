<?php

namespace App\Modules\Documents\Actions;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

class DownloadFile extends \App\Runtime\BaseActionController
{

	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();

		if (!\App\Modules\Users\Models\Privileges::isPermitted($moduleName, 'DetailView', $request->get('record'))) {
			throw new \Exception\NoPermittedToRecord(\App\Runtime\Vtiger_Language_Handler::translate('LBL_PERMISSION_DENIED', $moduleName));
		}
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();

		$documentRecordModel = \App\Modules\Vtiger\Models\Record::getInstanceById($request->get('record'), $moduleName);
		//Download the file
		$documentRecordModel->downloadFile();
		//Update the Download Count
		$documentRecordModel->updateDownloadCount();
	}
}
