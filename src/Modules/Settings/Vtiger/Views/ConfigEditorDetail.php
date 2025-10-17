<?php

namespace FreeCRM\Modules\Settings\Vtiger\Views;
use FreeCRM\Modules\Settings\Vtiger\Models\ConfigModule;


/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * ********************************************************************************** */

class ConfigEditorDetail extends \FreeCRM\Modules\Settings\Vtiger\Views\Index
{

	public function process(\FreeCRM\Http\Vtiger_Request $request)
	{
		$qualifiedName = $request->getModule(false);
		$moduleModel = \FreeCRM\Modules\Settings\Vtiger\Models\ConfigModule::getInstance();

		$viewer = $this->getViewer($request);
		$viewer->assign('MODEL', $moduleModel);
		$viewer->view('ConfigEditorDetail.tpl', $qualifiedName);
	}

	public function getPageTitle(\FreeCRM\Http\Vtiger_Request $request)
	{
		$qualifiedModuleName = $request->getModule(false);
		return \FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_CONFIG_EDITOR', $qualifiedModuleName);
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
			"modules.Settings.$moduleName.resources.ConfigEditor"
		);

		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		$headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);
		return $headerScriptInstances;
	}
}
