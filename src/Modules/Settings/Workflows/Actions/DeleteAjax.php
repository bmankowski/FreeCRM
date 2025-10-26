<?php

namespace App\Modules\Settings\Workflows\Actions;


/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

class DeleteAjax extends \App\Modules\Settings\Base\Actions\Index
{

	public function process(\App\Http\Vtiger_Request $request)
	{
		$qualifiedModule = $request->getModule(false);
		$recordId = $request->get('record');

		$response = new \App\Http\Vtiger_Response();
		$recordModel = \App\Modules\Settings\Workflows\Models\Record::getInstance($recordId);
		if ($recordModel->isDefault()) {
			$response->setError('LBL_DEFAULT_WORKFLOW', \App\Runtime\Vtiger_Language_Handler::translate('LBL_CANNOT_DELETE_DEFAULT_WORKFLOW', $qualifiedModule));
		} else {
			$recordModel->delete();
			$response->setResult(array('success' => 'ok'));
		}
		$response->emit();
	}

	public function validateRequest(\App\Http\Vtiger_Request $request)
	{
		$request->validateWriteAccess();
	}
}
