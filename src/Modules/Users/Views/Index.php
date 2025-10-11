<?php

namespace FreeCRM\Modules\Users\Views;

/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * ********************************************************************************** */


use FreeCRM\Http\Vtiger_Request;
class Index extends \Vtiger_Index_View
{

	public function preProcess(\FreeCRM\Http\Vtiger_Request $request, $display = true)
	{
		parent::preProcess($request);
		$currentUserModel = \FreeCRM\Modules\Users\Models\Record::getCurrentUserModel();
		if ($currentUserModel->isAdminUser()) {
			$settingsIndexView = new \FreeCRM\Modules\Settings\Vtiger\Views\Index();
			$settingsIndexView->preProcessSettings($request);
		}
	}

	public function postProcess(\FreeCRM\Http\Vtiger_Request $request)
	{
		$currentUserModel = \FreeCRM\Modules\Users\Models\Record::getCurrentUserModel();
		if ($currentUserModel->isAdminUser()) {
			$settingsIndexView = new \FreeCRM\Modules\Settings\Vtiger\Views\Index();
			$settingsIndexView->postProcessSettings($request);
		}
		parent::postProcess($request);
	}

	public function process(\FreeCRM\Http\Vtiger_Request $request)
	{
		
	}

	/**
	 * Function to get the list of Script models to be included
	 * @param Vtiger_Request $request
	 * @return <Array> - List of \FreeCRM\Modules\Vtiger\Models\JsScript instances
	 */
	public function getFooterScripts(\FreeCRM\Http\Vtiger_Request $request)
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
