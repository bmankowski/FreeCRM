<?php

namespace App\Modules\Settings\Profiles\Views;


/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * *********************************************************************************** */

class Settings_Profiles_Edit_View extends \App\Modules\Settings\Vtiger\Views\Index
{

	public function getBreadcrumbTitle(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		if ($request->get('record')) {
			$recordModel = \App\Modules\Settings\Profiles\Model\Record::getInstanceById($request->get('record'));
			$title = $recordModel->getName();
		} else {
			$title = \App\Runtime\Vtiger_Language_Handler::translate('LBL_VIEW_EDIT', $moduleName);
		}
		return $title;
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$this->initialize($request);
		$qualifiedModuleName = $request->getModule(false);

		$viewer = $this->getViewer($request);
		$viewer->view('EditView.tpl', $qualifiedModuleName);
	}

	public function initialize(\App\Http\Vtiger_Request $request)
	{
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		$qualifiedModuleName = $request->getModule(false);
		$record = $request->get('record');
		$fromRecord = $request->get('from_record');

		if (!empty($record)) {
			$recordModel = \App\Modules\Settings\Profiles\Model\Record::getInstanceById($record);
			$viewer->assign('MODE', 'edit');
		} elseif (!empty($fromRecord)) {
			$recordModel = \App\Modules\Settings\Profiles\Model\Record::getInstanceById($fromRecord);
			$recordModel->getModulePermissions();
			$recordModel->getGlobalPermissions();
			$recordModel->set('profileid', '');
			$viewer->assign('MODE', '');
			$viewer->assign('IS_DUPLICATE_RECORD', $fromRecord);
		} else {
			$recordModel = new \App\Modules\Settings\Profiles\Model\Record();
			$viewer->assign('MODE', '');
		}
		$viewer->assign('ALL_PROFILES', $recordModel->getAll());
		$viewer->assign('QUALIFIED_MODULE', $qualifiedModuleName);
		$viewer->assign('ALL_BASIC_ACTIONS', \App\Modules\Vtiger\Models\Action::getAllBasic(true));
		$viewer->assign('ALL_UTILITY_ACTIONS', \App\Modules\Vtiger\Models\Action::getAllUtility(true));
		$viewer->assign('RECORD_MODEL', $recordModel);
		$viewer->assign('RECORD_ID', $record);
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('USER_MODEL', \App\Modules\Users\Models\Record::getCurrentUserModel());
	}

	/**
	 * Function to get the list of Script models to be included
	 * @param \App\Http\Vtiger_Request $request
	 * @return <Array> - List of Vtiger_JsScript_Model instances
	 */
	public function getFooterScripts(\App\Http\Vtiger_Request $request)
	{
		$headerScriptInstances = parent::getFooterScripts($request);
		$moduleName = $request->getModule();

		$jsFileNames = array(
			'modules.Settings.Vtiger.resources.Edit',
			"modules.Settings.$moduleName.resources.Edit"
		);

		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		$headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);
		return $headerScriptInstances;
	}
}
