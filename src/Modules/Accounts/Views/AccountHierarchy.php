<?php

namespace App\Modules\Accounts\Views;

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
class AccountHierarchy extends \App\Runtime\BaseViewController
{

	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$userPrivilegesModel = \App\Modules\Users\Models\Privileges::getCurrentUserPrivilegesModel();
		$permission = $userPrivilegesModel->hasModulePermission($moduleName);

		if (!$permission) {
			throw new \App\Exceptions\NoPermitted('LBL_PERMISSION_DENIED');
		}
	}

	public function preProcess(\App\Http\Vtiger_Request $request, $display = true)
	{
		
	}

	private function getLastModified($id)
	{
		return (new \App\Db\Query())->from('u_#__crmentity_last_changes')
				->where(['crmid' => $id, 'fieldname' => 'active'])
				->one();
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		$recordId = $request->get('record');

		$recordModel = \App\Modules\Base\Models\Record::getInstanceById($recordId, $moduleName);
		$hierarchy = $recordModel->getAccountHierarchy();
		$listColumns = \App\AppConfig::module('Accounts', 'COLUMNS_IN_HIERARCHY');
		$lastModifiedField = [];
		if (!empty($listColumns) && in_array('active', $listColumns)) {
			foreach ($hierarchy['entries'] as $crmId => $entry) {
				$lastModified = $this->getLastModified($crmId);
				if ($lastModified) {
					$lastModifiedField[$crmId]['active']['userModel'] = \App\Modules\Base\Models\Record::getInstanceById($lastModified['user_id'], 'Users');
					$lastModifiedField[$crmId]['active']['changedon'] = (new \App\Fields\DateTimeField($lastModified['date_updated']))->getFullcalenderDateTimevalue();
				}
			}
		}
		$viewer->assign('LAST_MODIFIED', $lastModifiedField);
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('ACCOUNT_HIERARCHY', $hierarchy);
		$viewer->view('AccountHierarchy.tpl', $moduleName);
	}

	public function postProcess(\App\Http\Vtiger_Request $request)
	{
		
	}
}
