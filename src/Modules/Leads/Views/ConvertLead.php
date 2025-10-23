<?php

namespace App\Modules\Leads\Views;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * *********************************************************************************** */


use App\Http\Vtiger_Request;
class ConvertLead  extends \App\Modules\Vtiger\Views\Index
{

	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$moduleModel = \App\Modules\Vtiger\Models\Module::getInstance($moduleName);
		if (!$moduleModel->isPermitted('ConvertLead')) {
			throw new \Exception\NoPermitted('LBL_PERMISSION_DENIED');
		}

		$recordPermission = \App\Modules\Users\Models\Privileges::isPermitted($moduleName, 'Save', $recordId);
		if (!$recordPermission) {
			throw new \Exception\NoPermittedToRecord('LBL_NO_PERMISSIONS_FOR_THE_RECORD');
		}

		$recordId = $request->get('record');
		$recordModel = \App\Modules\Vtiger\Models\Record::getInstanceById($recordId);
		if (!\App\Modules\Leads\Models\Module::checkIfAllowedToConvert($recordModel->get('leadstatus'))) {
			throw new \Exception\NoPermitted('LBL_PERMISSION_DENIED');
		}
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$currentUserPriviligeModel = \App\Modules\Users\Models\Privileges::getCurrentUserPrivilegesModel();

		$viewer = $this->getViewer($request);
		$recordId = $request->get('record');
		$moduleName = $request->getModule();

		$recordModel = \App\Modules\Vtiger\Models\Record::getInstanceById($recordId);
		$moduleModel = $recordModel->getModule();
		$marketingProcessConfig = \App\Modules\Vtiger\Models\Processes::getConfig('marketing', 'conversion');
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('USER_MODEL', \App\Modules\Users\Models\Record::getCurrentUserModel());
		$viewer->assign('CURRENT_USER_PRIVILEGE', $currentUserPriviligeModel);
		$viewer->assign('RECORD', $recordModel);
		$viewer->assign('CONVERT_LEAD_FIELDS', $recordModel->getConvertLeadFields());

		$assignedToFieldModel = $moduleModel->getField('assigned_user_id');
		if ($marketingProcessConfig['change_owner'] === 'true') {
			$assignedToFieldModel->set('fieldvalue', \App\Modules\Users\Models\Record::getCurrentUserId());
		} else {
			$assignedToFieldModel->set('fieldvalue', $recordModel->get('assigned_user_id'));
		}
		$viewer->assign('CONVERSION_CONFIG', $marketingProcessConfig);
		$viewer->assign('ASSIGN_TO', $assignedToFieldModel);
		$viewer->view('ConvertLead.tpl', $moduleName);
	}
}
