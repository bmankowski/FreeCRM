<?php

namespace App\Modules\Portal\Actions;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

class DeleteAjax extends \App\Base\Controllers\BaseActionController
{
	public function checkPermission(\App\Http\Vtiger_Request $request){
		return true;
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$recordId = $request->get('record');
		$module = $request->getModule();
		$moduleModel = new \App\Modules\Portal\Models\Module();
		$moduleModel->deleteRecord($recordId);

		$response = new \App\Http\Vtiger_Response();
		$response->setResult(array('message' => \App\Runtime\Vtiger_Language_Handler::translate('LBL_RECORD_DELETED_SUCCESSFULLY', $module)));
		$response->emit();
	}
}
