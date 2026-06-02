<?php

namespace App\Modules\Settings\Base\Views;
use App\Modules\Settings\Base\Models\CustomRecordNumberingModule;


/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * ********************************************************************************** */

class CustomRecordNumbering extends \App\Modules\Settings\Base\Views\Index
{

	public function process(\App\Http\Vtiger_Request $request)
	{
		$qualifiedModuleName = $request->getModule(false);
		$supportedModules = \App\Modules\Settings\Base\Models\CustomRecordNumberingModule::getSupportedModules();

		$sourceModule = $request->get('sourceModule');
		if ($sourceModule) {
			$defaultModuleModel = $supportedModules[\App\Utils\ModuleUtils::getModuleId($sourceModule)];
		} else {
			$defaultModuleModel = reset($supportedModules);
		}

		$viewer = $this->getViewer($request);
		$viewer->assign('SUPPORTED_MODULES', $supportedModules);
		$viewer->assign('DEFAULT_MODULE_MODEL', $defaultModuleModel);
		
		// Prepare CustomRecordNumbering-specific data for CustomRecordNumbering template
		$this->prepareCustomRecordNumberingData($viewer, $defaultModuleModel);
		
		// Check if this is an AJAX request - if so, return only content without MainLayout
		if ($request->isAjax()) {
			$viewer->view('CustomRecordNumbering.tpl', $qualifiedModuleName);
		} else {
			$viewer->view('CustomRecordNumberingIndex.tpl', $qualifiedModuleName);
		}
	}
	
	/**
	 * Prepare data for CustomRecordNumbering template
	 * Moves function calls from template to controller for better MVC separation
	 */
	protected function prepareCustomRecordNumberingData($viewer, $defaultModuleModel)
	{
		$defaultModuleName = $defaultModuleModel->getName();
		$viewer->assign('DEFAULT_MODULE_DATA', \App\Fields\RecordNumber::getNumber($defaultModuleName));
	}

	public function getPageTitle(\App\Http\Vtiger_Request $request)
	{
		$qualifiedModuleName = $request->getModule(false);
		return \App\Runtime\Vtiger_Language_Handler::translate('LBL_CUSTOMIZE_RECORD_NUMBERING', $qualifiedModuleName);
	}

	/**
	 * Function to get the list of Script models to be included
	 * @param \App\Http\Vtiger_Request $request
	 * @return array - List of ScriptAsset instances
	 */
	public function getFooterScripts(\App\Http\Vtiger_Request $request)
	{
		$headerScriptInstances = parent::getFooterScripts($request);
		$moduleName = $request->getModule();

		$jsFileNames = array(
			'modules.Settings.Vtiger.resources.CustomRecordNumbering',
			'libraries.jquery.clipboardjs.clipboard',
			'modules.Settings.Vtiger.resources.Edit',
		);

		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		$headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);
		return $headerScriptInstances;
	}
}
