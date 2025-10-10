<?php

namespace FreeCRM\Modules\Reports\Actions;

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

	public function checkPermission(\FreeCRM\Http\Vtiger_Request $request)
	{
		if (!\FreeCRM\Modules\Users\Models\Privileges::getCurrentUserPrivilegesModel()->hasModulePermission($request->getModule())) {
			throw new \Exception\NoPermitted('LBL_PERMISSION_DENIED');
		}
	}
	
	public function process(\FreeCRM\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$response = new \FreeCRM\Http\Vtiger_Response();
		$recordModel = \FreeCRM\Modules\Reports\Models\Record::getInstanceById($request->get('record'), $moduleName);
		if (!$recordModel->isDefault() && $recordModel->isEditable()) {
			$recordModel->delete();
			$response->setResult([LanguageTranslator::translate('LBL_REPORTS_DELETED_SUCCESSFULLY', $moduleName)]);
		} else {
			$response->setError(LanguageTranslator::translate('LBL_REPORT_DELETE_DENIED', $moduleName));
		}
		$response->emit();
	}
}
