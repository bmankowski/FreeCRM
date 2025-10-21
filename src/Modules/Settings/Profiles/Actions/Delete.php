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

class Delete extends \App\Modules\Settings\Vtiger\Actions\Basic
{

	public function process(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$qualifiedModuleName = $request->getModule(false);
		$recordId = $request->get('record');
		$transferRecordId = $request->get('transfer_record');

		$moduleModel = \App\Modules\Settings\Vtiger\Models\Module::getInstance($qualifiedModuleName);
		$recordModel = \App\Modules\Settings\Profiles\Model\Record::getInstanceById($recordId);
		$transferToProfile = \App\Modules\Settings\Profiles\Model\Record::getInstanceById($transferRecordId);
		if ($recordModel && $transferToProfile) {
			$recordModel->delete($transferToProfile);
		}

		$response = new \App\Http\Vtiger_Response();
		$result = array('success' => true);

		$response->setResult($result);
		$response->emit();
	}

	public function validateRequest(\App\Http\Vtiger_Request $request)
	{
		$request->validateWriteAccess();
	}
}
