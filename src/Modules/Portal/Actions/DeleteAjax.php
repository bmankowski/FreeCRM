<?php

namespace FreeCRM\Modules\Portal\Actions;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

class DeleteAjax extends \FreeCRM\Runtime\Vtiger_Action_Controller
{
	public function checkPermission(\FreeCRM\Http\Vtiger_Request $request){
		return true;
	}

	public function process(\FreeCRM\Http\Vtiger_Request $request)
	{
		$recordId = $request->get('record');
		$module = $request->getModule();
		$moduleModel = new Portal_Module_Model();
		$moduleModel->deleteRecord($recordId);

		$response = new \FreeCRM\Http\Vtiger_Response();
		$response->setResult(array('message' => \FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_RECORD_DELETED_SUCCESSFULLY', $module)));
		$response->emit();
	}
}
