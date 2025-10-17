<?php

namespace App\Modules\Settings\SMSNotifier\Actions;
use App\Modules\Settings\SMSNotifierModels\Module;


/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

class Delete extends \App\Modules\Settings\Vtiger\Actions\Index
{

	public function process(\App\Http\Vtiger_Request $request)
	{
		$recordId = $request->get('record');
		$qualifiedModuleName = $request->getModule(false);

		$response = new \App\Http\Vtiger_Response();
		if ($recordId) {
			$status = \App\Modules\Settings\SMSNotifier\Models\Module::deleteRecords(array($recordId));
			if ($status) {
				$response->setResult(array(\App\Runtime\Vtiger_Language_Handler::translate('LBL_DELETED_SUCCESSFULLY'), $qualifiedModuleName));
			} else {
				$response->setError(\App\Runtime\Vtiger_Language_Handler::translate('LBL_DELETE_FAILED', $qualifiedModuleName));
			}
		} else {
			$response->setError(\App\Runtime\Vtiger_Language_Handler::translate('LBL_INVALID_RECORD', $qualifiedModuleName));
		}
		$response->emit();
	}

	public function validateRequest(\App\Http\Vtiger_Request $request)
	{
		$request->validateWriteAccess();
	}
}
