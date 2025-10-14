<?php

namespace FreeCRM\Modules\Settings\Roles\Actions;


/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

class Delete extends \FreeCRM\Modules\Settings\Vtiger\Actions\Basic
{

	public function process(\FreeCRM\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$qualifiedModuleName = $request->getModule(false);
		$recordId = $request->get('record');
		$transferRecordId = $request->get('transfer_record');

		$moduleModel = Settings_Vtiger_Module_Model::getInstance($qualifiedModuleName);
		$recordModel = \FreeCRM\Modules\Settings\Roles\Models\Record::getInstanceById($recordId);
		$transferToRole = \FreeCRM\Modules\Settings\Roles\Models\Record::getInstanceById($transferRecordId);
		if ($recordModel && $transferToRole) {
			$recordModel->delete($transferToRole);
		}

		$redirectUrl = $moduleModel->getDefaultUrl();
		header("Location: $redirectUrl");
	}

	public function validateRequest(\FreeCRM\Http\Vtiger_Request $request)
	{
		$request->validateWriteAccess();
	}
}
