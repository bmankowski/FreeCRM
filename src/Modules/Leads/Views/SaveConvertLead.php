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

class SaveConvertLead extends \App\Base\Controllers\BaseViewController
{

	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$recordId = $request->get('record');

		$currentUserPriviligesModel = \App\Modules\Users\Models\Privileges::getCurrentUserPrivilegesModel();
		if (!$currentUserPriviligesModel->hasModuleActionPermission($moduleName, 'ConvertLead')) {
			throw new \App\Exceptions\NoPermitted('LBL_PERMISSION_DENIED');
		}

		$recordPermission = \App\Privilege::isPermitted($moduleName, 'EditView', $recordId);
		if (!$recordPermission) {
			throw new \App\Exceptions\NoPermittedToRecord('LBL_NO_PERMISSIONS_FOR_THE_RECORD');
		}

		$recordModel = \App\Modules\Base\Models\Record::getInstanceById($recordId);
		if (!\App\Modules\Leads\Models\Module::checkIfAllowedToConvert($recordModel->get('leadstatus'))) {
			throw new \App\Exceptions\NoPermitted('LBL_PERMISSION_DENIED');
		}
	}

	public function preProcess(\App\Http\Vtiger_Request $request, $display = true)
	{
		
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$recordId = $request->get('record');
		$modules = $request->get('modules');
		$assignId = $request->get('assigned_user_id');
		$currentUser = $request->getUser();

		$entityValues = [];
		$entityValues['transferRelatedRecordsTo'] = $request->get('transferModule');
		$entityValues['assignedTo'] = $assignId;
		$entityValues['leadId'] = $recordId;
		$createAlways = \App\Modules\Base\Models\Processes::getConfig('marketing', 'conversion', 'create_always');

		$recordModel = \App\Modules\Base\Models\Record::getInstanceById($recordId, $request->getModule());
		$convertLeadFields = $recordModel->getConvertLeadFields();
		$availableModules = ['Accounts'];
		foreach ($availableModules as $module) {
			if (\App\Utils\ModuleUtils::isModuleActive($module) && in_array($module, $modules)) {
				$entityValues['entities'][$module]['create'] = true;
				$entityValues['entities'][$module]['name'] = $module;
				foreach ($convertLeadFields[$module] as $fieldModel) {
					$fieldName = $fieldModel->getName();
					$fieldValue = $fieldModel->getUITypeModel()->getDBValue($request->get($fieldName, null));
					$entityValues['entities'][$module][$fieldName] = $fieldValue;
				}
			}
		}
		try {
			$results = true;
			if ($createAlways === true || $createAlways === 'true') {
				$leadModel = \App\Modules\Base\Models\Module::getCleanInstance($request->getModule());
				$results = $leadModel->searchAccountsToConvert($recordModel);
				$entityValues['entities']['Accounts']['convert_to_id'] = $results;
			}
			if (!$results) {
				$message = \App\Runtime\Vtiger_Language_Handler::translate('LBL_TOO_MANY_ACCOUNTS_TO_CONVERT', $request->getModule(), '');
				if ($currentUser->isAdminUser()) {
					$message = \App\Runtime\Vtiger_Language_Handler::translate('LBL_TOO_MANY_ACCOUNTS_TO_CONVERT', $request->getModule(), '<a href="index.php?module=MarketingProcesses&view=Index&parent=Settings"><span class="glyphicon glyphicon-folder-open"></span></a>');
				}
				$this->showError($request, '', $message);
				throw new \App\Exceptions\AppException('LBL_TOO_MANY_ACCOUNTS_TO_CONVERT');
			}
		} catch (Exception $e) {
			$this->showError($request, $e);
			throw new \App\Exceptions\AppException($e->getMessage());
		}
		try {
			$result = vtws_convertlead($entityValues, $currentUser);
		} catch (Exception $e) {
			$this->showError($request, $e);
			throw new \App\Exceptions\AppException($e->getMessage());
		}

		if (!empty($result['Accounts'])) {
			$accountId = $result['Accounts'];
		}

		if (!empty($accountId)) {
			\App\Modules\ModTracker\Models\Record::addConvertToAccountRelation('Accounts', $accountId, $assignId);
			header("Location: index.php?view=Detail&module=Accounts&record=$accountId");
		} else {
			$this->showError($request);
			throw new \App\Exceptions\AppException('Error');
		}
	}

	public function showError($request, $exception = false, $message = '')
	{
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		$currentUser = $request->getUser();

		if ($exception != false) {
			$viewer->assign('EXCEPTION', \App\Runtime\Vtiger_Language_Handler::translate($exception->getMessage(), $moduleName));
		} elseif ($message) {
			$viewer->assign('EXCEPTION', $message);
		}

		$viewer->assign('CURRENT_USER', $currentUser);
		$viewer->assign('MODULE', $moduleName);
		$viewer->view('ConvertLeadError.tpl', $moduleName);
	}

	public function validateRequest(\App\Http\Vtiger_Request $request)
	{
		$request->validateWriteAccess();
	}
}
