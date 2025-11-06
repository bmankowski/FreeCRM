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



class PreferenceDetail extends \App\Modules\Base\Views\Detail
{

	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		$currentUserModel = $request->getUser();
		$record = $request->get('record');

		if (!\App\AppConfig::security('SHOW_MY_PREFERENCES')) {
			throw new \App\Exceptions\NoPermittedToRecord('LBL_PERMISSION_DENIED');
		}
		if ($currentUserModel->isAdminUser() === true || $currentUserModel->get('id') == $record) {
			return true;
		} else {
			throw new \App\Exceptions\NoPermittedToRecord('LBL_PERMISSION_DENIED');
		}
	}

	/**
	 * Function shows basic detail for the record
	 * @param <type> $request
	 */
	public function showModuleBasicView(\App\Http\Vtiger_Request $request)
	{
		return $this->showModuleDetailView($request);
	}

	public function preProcess(\App\Http\Vtiger_Request $request, $display = true)
	{
		parent::preProcess($request, false);
		// MainLayout handles rendering, no separate preProcess template needed
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$recordId = $request->get('record');
		$moduleName = $request->getModule();
		$viewer = $this->getViewer($request);
		$currentUser = $request->getUser();

		$recordModel = \App\Modules\Base\Models\Record::getInstanceById($recordId, $moduleName);
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
		$viewer->assign('USER_MODEL', $currentUser);

		$homeModuleModel = \App\Modules\Base\Models\Module::getInstance('Home');
		$viewer->assign('HOME_MODULE_MODEL', $homeModuleModel);
		$viewer->assign('MENU_HEADER_LINKS', $this->getMenuHeaderLinks($request));
		$viewer->assign('SEARCHABLE_MODULES', \App\Modules\Base\Models\Module::getSearchableModules());
		$viewer->assign('CHAT_ACTIVE', \App\Module::isModuleActive('AJAXChat'));
		$viewer->assign('REMINDER_ACTIVE', $activeReminder);
		$viewer->assign('SHOW_BODY_HEADER', $this->showBodyHeader());

		// Detail view specific assignments
		$detailViewModel = \App\Modules\Base\Models\DetailView::getInstance($moduleName, $recordId);
		$detailViewLinkParams = array('MODULE' => $moduleName, 'RECORD' => $recordId);
		$detailViewLinks = $detailViewModel->getDetailViewLinks($detailViewLinkParams);
		$viewer->assign('RECORD', $recordModel);
		$viewer->assign('MODULE_MODEL', $detailViewModel->getModule());
		$viewer->assign('DETAILVIEW_LINKS', $detailViewLinks);
		$viewer->assign('IS_EDITABLE', $detailViewModel->getRecord()->isEditable($moduleName));
		$viewer->assign('IS_DELETABLE', $detailViewModel->getRecord()->isDeletable($moduleName));

		$linkParams = array('MODULE' => $moduleName, 'ACTION' => $request->get('view'));
		$linkModels = $detailViewModel->getSideBarLinks($linkParams);

		// Process sidebar links to determine active link
		$activeLinkLabel = $this->processSidebarLinks($linkModels, $request);

		$viewer->assign('QUICK_LINKS', $linkModels);
		$viewer->assign('ACTIVE_SIDEBAR_LINK', $activeLinkLabel);

		$viewer->assign('DAY_STARTS', \App\Json::encode($dayStartPicklistValues));
		$viewer->assign('IMAGE_DETAILS', $recordModel->getImageDetails());

		return parent::process($request);
	}

	public function getFooterScripts(\App\Http\Vtiger_Request $request)
	{
		$headerScriptInstances = parent::getFooterScripts($request);
		$moduleName = $request->getModule();
		$moduleDetailFile = 'modules.' . $moduleName . '.resources.PreferenceDetail';
		unset($headerScriptInstances[$moduleDetailFile]);

		$jsFileNames = array(
			'modules.Base.resources.Detail',
			'modules.Base.resources.RelatedList',
			'modules.Base.resources.ListSearch',
			'modules.Users.resources.Detail',
			'modules.' . $moduleName . '.resources.PreferenceDetail',
			'modules.' . $moduleName . '.resources.PreferenceEdit'
		);

		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		$headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);
		return $headerScriptInstances;
	}
}
