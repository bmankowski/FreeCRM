<?php

namespace FreeCRM\Modules\Settings\Vtiger\Actions;
use FreeCRM\Modules\Settings\Vtiger\Models\ConfigModule;


/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * ********************************************************************************** */

class ConfigEditorSaveAjax extends \FreeCRM\Modules\Settings\Vtiger\Actions\Basic
{

	public function process(\FreeCRM\Http\Vtiger_Request $request)
	{
		$response = new \FreeCRM\Http\Vtiger_Response();
		$qualifiedModuleName = $request->getModule(false);
		$updatedFields = $request->get('updatedFields');
		$moduleModel = \FreeCRM\Modules\Settings\Vtiger\Models\ConfigModule::getInstance();

		if ($updatedFields) {
			$moduleModel->set('updatedFields', $updatedFields);
			$status = $moduleModel->save();

			if ($status === true) {
				$response->setResult(array($status));
			} else {
				$response->setError(vtranslate($status, $qualifiedModuleName));
			}
		} else {
			$response->setError(vtranslate('LBL_FIELDS_INFO_IS_EMPTY', $qualifiedModuleName));
		}
		$response->emit();
	}

	public function validateRequest(\FreeCRM\Http\Vtiger_Request $request)
	{
		$request->validateWriteAccess();
	}
}
