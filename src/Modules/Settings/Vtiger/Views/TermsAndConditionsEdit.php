<?php

namespace FreeCRM\Modules\Settings\Vtiger\Views;
use FreeCRM\Modules\Settings\Vtiger\Models\TermsAndConditions;


/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * ********************************************************************************** */

class TermsAndConditionsEdit extends \FreeCRM\Modules\Settings\Vtiger\Views\Index
{

	public function process(\FreeCRM\Http\Vtiger_Request $request)
	{
		$model = \FreeCRM\Modules\Settings\Vtiger\Models\TermsAndConditions::getInstance();
		$conditionText = $model->getText();

		$viewer = $this->getViewer($request);
		$qualifiedName = $request->getModule(false);

		$viewer->assign('CONDITION_TEXT', $conditionText);
		$viewer->assign('MODEL', $model);
		$viewer->view('TermsAndConditions.tpl', $qualifiedName);
	}

	public function getPageTitle(\FreeCRM\Http\Vtiger_Request $request)
	{
		$qualifiedModuleName = $request->getModule(false);
		return vtranslate('INVENTORYTERMSANDCONDITIONS', $qualifiedModuleName);
	}

	/**
	 * Function to get the list of Script models to be included
	 * @param \FreeCRM\Http\Vtiger_Request $request
	 * @return <Array> - List of Vtiger_JsScript_Model instances
	 */
	public function getFooterScripts(\FreeCRM\Http\Vtiger_Request $request)
	{
		$headerScriptInstances = parent::getFooterScripts($request);
		$moduleName = $request->getModule();

		$jsFileNames = array(
			"modules.Settings.$moduleName.resources.TermsAndConditions"
		);

		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		$headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);
		return $headerScriptInstances;
	}
}
