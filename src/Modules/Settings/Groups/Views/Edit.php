<?php

namespace App\Modules\Settings\Groups\Views;


/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

class Edit extends \App\Modules\Settings\Base\Views\Index
{

	public function process(\App\Http\Vtiger_Request $request)
	{
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		$qualifiedModuleName = $request->getModule(false);
		$record = $request->get('record');

		if (!empty($record)) {
			$recordModel = \App\Modules\Settings\Groups\Models\Record::getInstance($record);
			$viewer->assign('MODE', 'edit');
		} else {
			$recordModel = new \App\Modules\Settings\Groups\Models\Record();
			$viewer->assign('MODE', '');
		}

		$viewer->assign('MEMBER_GROUPS', \App\Modules\Settings\Groups\Models\Member::getAll(true));
		$viewer->assign('RECORD_MODEL', $recordModel);
		$viewer->assign('RECORD_ID', $record);
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('USER_MODEL', $request->getUser());
		
		// Prepare Groups EditView-specific data for EditView template
		$this->prepareGroupsEditViewData($viewer);
		
		$viewer->view('EditView.tpl', $qualifiedModuleName);
	}
	
	/**
	 * Prepare data for Groups EditView template
	 * Moves function calls from template to controller for better MVC separation
	 */
	protected function prepareGroupsEditViewData($viewer)
	{
		$viewer->assign('ALL_MODULES', \App\Modules\Base\Models\Module::getAll([0], [], true));
	}

	/**
	 * Function to get the list of Script models to be included
	 * @param \App\Http\Vtiger_Request $request
	 * @return <Array> - List of ScriptAsset instances
	 */
	public function getFooterScripts(\App\Http\Vtiger_Request $request)
	{
		$headerScriptInstances = parent::getFooterScripts($request);
		$moduleName = $request->getModule();

		$jsFileNames = array(
			"modules.Settings.$moduleName.resources.Edit"
		);

		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		$headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);
		return $headerScriptInstances;
	}
}
