<?php

namespace App\Modules\Users\Views;

/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * ********************************************************************************** */


use App\Http\Vtiger_Request;
class Index extends \App\Modules\Vtiger\Views\Index
{

	public function preProcess(\App\Http\Vtiger_Request $request, $display = true)
	{
		parent::preProcess($request);
		$currentUserModel = \App\Modules\Users\Models\Record::getCurrentUserModel();
		if ($currentUserModel->isAdminUser()) {
			$settingsIndexView = new \App\Modules\Settings\Vtiger\Views\Index();
			$settingsIndexView->preProcessSettings($request);
		}
	}

	public function postProcess(\App\Http\Vtiger_Request $request)
	{
		$currentUserModel = \App\Modules\Users\Models\Record::getCurrentUserModel();
		if ($currentUserModel->isAdminUser()) {
			$settingsIndexView = new \App\Modules\Settings\Vtiger\Views\Index();
			$settingsIndexView->postProcessSettings($request);
		}
		parent::postProcess($request);
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		
	}

	/**
	 * Function to get the list of Script models to be included
	 * @param Vtiger_Request $request
	 * @return <Array> - List of \App\Modules\Vtiger\Models\JsScript instances
	 */
	public function getFooterScripts(\App\Http\Vtiger_Request $request)
	{
		$headerScriptInstances = parent::getFooterScripts($request);
		$moduleName = $request->getModule();

		$jsFileNames = array(
			'modules.Vtiger.resources.Vtiger',
			"modules.$moduleName.resources.$moduleName",
		);

		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		$headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);
		return $headerScriptInstances;
	}
}
