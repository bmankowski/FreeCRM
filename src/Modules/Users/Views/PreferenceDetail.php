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

		if (!\App\Core\AppConfig::security('SHOW_MY_PREFERENCES')) {
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
		
		// Prepare all preference detail data
		$recordId = $request->get('record');
		$moduleName = $request->getModule();
		$viewer = $this->getViewer($request);
		$currentUser = $request->getUser();

		$recordModel = \App\Modules\Base\Models\Record::getInstanceById($recordId, $moduleName);
		$recordStructureInstance = \App\Modules\Base\Models\RecordStructure::getInstanceFromRecordModel($recordModel, \App\Modules\Base\Models\RecordStructure::RECORD_STRUCTURE_MODE_EDIT);
		$dayStartPicklistValues = \App\Modules\Users\Models\Record::getDayStartsPicklistValues($recordStructureInstance->getStructure());
		
		// Unique assignments for PreferenceDetail
		$viewer->assign('QUALIFIED_MODULE', $moduleName);
		$viewer->assign('MENUS', \App\Modules\Base\Models\Menu::getAll(true));
		$viewer->assign('USER_MODEL', $currentUser);

		// Detail view specific assignments
		$detailViewModel = \App\Modules\Base\Models\DetailView::getInstance($moduleName, $recordId);
		$detailViewLinkParams = array('MODULE' => $moduleName, 'RECORD' => $recordId, 'VIEW' => $request->get('view'));
		$detailViewLinks = $detailViewModel->getDetailViewLinks($detailViewLinkParams);
		$viewer->assign('RECORD', $recordModel);
		$viewer->assign('MODULE_MODEL', $detailViewModel->getModule());
		$viewer->assign('DETAILVIEW_LINKS', $detailViewLinks);
		$viewer->assign('IS_EDITABLE', $detailViewModel->getRecord()->isEditable($moduleName));
		$viewer->assign('IS_DELETABLE', $detailViewModel->getRecord()->isDeletable($moduleName));

		$linkParams = array('MODULE' => $moduleName, 'ACTION' => $request->get('view'));
		$linkModels = $detailViewModel->getSideBarLinks($linkParams, $request->getUser());

		// Process sidebar links to determine active link
		$activeLinkLabel = $this->processSidebarLinks($linkModels, $request);

		$viewer->assign('QUICK_LINKS', $linkModels);
		$viewer->assign('ACTIVE_SIDEBAR_LINK', $activeLinkLabel);

		$viewer->assign('DAY_STARTS', \App\Utils\Json::encode($dayStartPicklistValues));
		$viewer->assign('IMAGE_DETAILS', $recordModel->getImageDetails());
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		// Data already assigned in preProcess, just render
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
