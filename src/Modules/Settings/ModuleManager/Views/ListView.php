<?php

namespace App\Modules\Settings\ModuleManager\Views;


/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

class ListView extends \App\Modules\Settings\Base\Views\Index
{

	public function preProcess(\App\Http\Vtiger_Request $request, $display = true)
	{
		parent::preProcess($request, false);
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		$qualifiedModuleName = $request->getModule(false);
	
		$viewer->assign('ALL_MODULES', \App\Modules\Settings\ModuleManager\Models\Module::getAll());
		$viewer->assign('RESTRICTED_MODULES_LIST', \App\Modules\Settings\ModuleManager\Models\Module::getActionsRestrictedModulesList());
		$viewer->assign('IMPORT_MODULE_URL', \App\Modules\Settings\ModuleManager\Models\Module::getNewModuleImportUrl());
		$viewer->assign('IMPORT_USER_MODULE_URL', \App\Modules\Settings\ModuleManager\Models\Module::getUserModuleImportUrl());
		$viewer->assign('ALL_LIBRARIES', \App\Modules\Settings\ModuleManager\Models\Library::getAll());
		$viewer->assign('SYSTEM_MODE', \App\Core\AppConfig::main('systemMode'));
	}
	
	public function process(\App\Http\Vtiger_Request $request)
	{
		$viewer = $this->getViewer($request);
		$qualifiedModuleName = $request->getModule(false);
	
		if ($request->isAjax()) {
			// AJAX request - return content only
			$viewer->view('ListContent.tpl', $qualifiedModuleName);
		} else {
			// Initial page load - return full page with MainLayout
			$viewer->view('ListViewIndex.tpl', $qualifiedModuleName);
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function getFooterScripts(\App\Http\Vtiger_Request $request)
	{
		$footerScripts = parent::getFooterScripts($request);
		$moduleName = $request->getModule();

		$jsFileNames = [
			"modules.Settings.$moduleName.resources.ModuleManager",
		];

		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		return array_merge($footerScripts, $jsScriptInstances);
	}
}
