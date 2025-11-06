<?php

namespace App\Modules\Users\Views;
/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * *********************************************************************************** */

class PreferenceEdit extends \App\Modules\Base\Views\Edit
{

	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$currentUserModel = $request->getUser();
		$record = $request->get('record');
		if (!\App\AppConfig::security('SHOW_MY_PREFERENCES')) {
			throw new \App\Exceptions\NoPermittedToRecord('LBL_PERMISSION_DENIED');
		}
		if (!empty($record) && $currentUserModel->get('id') != $record) {
			$recordModel = \App\Modules\Base\Models\Record::getInstanceById($record, $moduleName);
			if ($recordModel->get('status') != 'Active') {
				throw new \App\Exceptions\NoPermittedToRecord('LBL_PERMISSION_DENIED');
			}
		}
		if (($currentUserModel->isAdminUser() === true || $currentUserModel->get('id') == $record)) {
			return true;
		} else {
			throw new \App\Exceptions\NoPermittedToRecord('LBL_PERMISSION_DENIED');
		}
	}

	public function preProcess(\App\Http\Vtiger_Request $request, $display = true)
	{
		// Call parent with false to prevent old-style template rendering
		parent::preProcess($request, false);
		// MainLayout handles rendering, no separate preProcess template needed
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$recordId = $request->get('record');
		$viewer = $this->getViewer($request);
		$currentUser = $request->getUser();

		if (!empty($recordId)) {
			$recordModel = \App\Modules\Base\Models\Record::getInstanceById($recordId, $moduleName);
		} else {
			$recordModel = \App\Modules\Base\Models\Record::getCleanInstance($moduleName);
		}

		$recordStructureInstance = \App\Modules\Base\Models\RecordStructure::getInstanceFromRecordModel($recordModel, \App\Modules\Base\Models\RecordStructure::RECORD_STRUCTURE_MODE_EDIT);
		$dayStartPicklistValues = \App\Modules\Users\Models\Record::getDayStartsPicklistValues($recordStructureInstance->getStructure());

		// Assignments moved from preProcess
		if ($activeReminder = \App\Module::isModuleActive('Calendar')) {
			$userPrivilegesModel = \App\Modules\Users\Models\Privileges::getCurrentUserPrivilegesModel();
			$activeReminder = $userPrivilegesModel->hasModulePermission('Calendar');
		}
		$selectedModule = $request->getModule();
		$currentDate = \App\Modules\Base\UiTypes\Date::getDisplayDateValue(date('Y-n-j'));
		$viewer->assign('CURRENTDATE', $currentDate);
		$viewer->assign('MODULE', $selectedModule);
		$viewer->assign('MODULE_NAME', $selectedModule);
		$viewer->assign('QUALIFIED_MODULE', $selectedModule);
		$viewer->assign('PARENT_MODULE', $request->get('parent'));
		$viewer->assign('MENUS', \App\Modules\Base\Models\Menu::getAll(true));
		$viewer->assign('VIEW', $request->get('view'));

		$homeModuleModel = \App\Modules\Base\Models\Module::getInstance('Home');
		$viewer->assign('HOME_MODULE_MODEL', $homeModuleModel);
		$viewer->assign('MENU_HEADER_LINKS', $this->getMenuHeaderLinks($request));
		$viewer->assign('SEARCHABLE_MODULES', \App\Modules\Base\Models\Module::getSearchableModules());
		$viewer->assign('CHAT_ACTIVE', \App\Module::isModuleActive('AJAXChat'));
		$viewer->assign('REMINDER_ACTIVE', $activeReminder);
		$viewer->assign('SHOW_BODY_HEADER', $this->showBodyHeader());
		$viewer->assign('IS_PREFERENCE', true);

		$viewer->assign("DAY_STARTS", \App\Json::encode($dayStartPicklistValues));
		if (method_exists($recordModel, 'getImageDetails')) {
			$viewer->assign('IMAGE_DETAILS', $recordModel->getImageDetails());
		}
		$viewer->assign('USER_MODEL', $currentUser);

		parent::process($request);
	}
}
