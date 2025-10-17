<?php

namespace FreeCRM\Modules\Vtiger\Actions;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * *********************************************************************************** */

class MassDelete extends \FreeCRM\Runtime\Vtiger_Action_Controller
{

	public function checkPermission(\FreeCRM\Http\Vtiger_Request $request)
	{
		$currentUserPriviligesModel = \FreeCRM\Modules\Users\Models\Privileges::getCurrentUserPrivilegesModel();
		if (!$currentUserPriviligesModel->hasModuleActionPermission($request->getModule(), 'Delete')) {
			throw new \Exception\NoPermitted('LBL_PERMISSION_DENIED');
		}
	}

	public function preProcess(\FreeCRM\Http\Vtiger_Request $request)
	{
		return true;
	}

	public function postProcess(\FreeCRM\Http\Vtiger_Request $request)
	{
		return true;
	}

	public function process(\FreeCRM\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$moduleModel = \FreeCRM\Modules\Vtiger\Models\Module::getInstance($moduleName);

		if ($request->get('selected_ids') == 'all' && $request->get('mode') == 'FindDuplicates') {
			$recordIds = Vtiger_FindDuplicate_Model::getMassDeleteRecords($request);
		} else {
			$recordIds = $this->getRecordsListFromRequest($request);
		}
		foreach ($recordIds as $recordId) {
			if (\FreeCRM\Modules\Users\Models\Privileges::isPermitted($moduleName, 'Delete', $recordId)) {
				$recordModel = \FreeCRM\Modules\Vtiger\Models\Record::getInstanceById($recordId, $moduleModel);
				if ($recordModel->isDeletable()) {
					$recordModel->delete();
				}
			} else {
				$permission = 'No';
			}
		}

		if ($permission === 'No') {
			throw new \Exception\AppException(\FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_PERMISSION_DENIED'));
		}

		$cvId = $request->get('viewname');
		$response = new \FreeCRM\Http\Vtiger_Response();
		$response->setResult(array('viewname' => $cvId, 'module' => $moduleName));
		$response->emit();
	}
}
