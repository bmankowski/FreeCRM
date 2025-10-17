<?php

namespace FreeCRM\Modules\Settings\Workflows\Actions;


/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

use FreeCRM\Modules\Settings\Workflows\Models\Record as Settings_Workflows_Record_Model;
class DeleteAjax extends \FreeCRM\Modules\Settings\Vtiger\Actions\Index
{

	public function process(\FreeCRM\Http\Vtiger_Request $request)
	{
		$qualifiedModule = $request->getModule(false);
		$recordId = $request->get('record');

		$response = new \FreeCRM\Http\Vtiger_Response();
		$recordModel = Settings_Workflows_Record_Model::getInstance($recordId);
		if ($recordModel->isDefault()) {
			$response->setError('LBL_DEFAULT_WORKFLOW', \FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_CANNOT_DELETE_DEFAULT_WORKFLOW', $qualifiedModule));
		} else {
			$recordModel->delete();
			$response->setResult(array('success' => 'ok'));
		}
		$response->emit();
	}

	public function validateRequest(\FreeCRM\Http\Vtiger_Request $request)
	{
		$request->validateWriteAccess();
	}
}
