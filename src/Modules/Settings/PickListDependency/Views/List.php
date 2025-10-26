<?php

namespace App\Modules\Settings\PickListDependency\Views;


/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * ********************************************************************************** */

class List extends \App\Modules\Settings\Base\Views\List
{

	public function preProcess(\App\Http\Vtiger_Request $request, $display = true)
	{
		$moduleModelList = \App\Modules\Settings\PickListDependency\Models\Module::getPicklistSupportedModules();
		$forModule = $request->get('formodule');
		$viewer = $this->getViewer($request);
		$viewer->assign('PICKLIST_MODULES_LIST', $moduleModelList);
		$viewer->assign('FOR_MODULE', $forModule);
		parent::preProcess($request, $display);
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		if ($request->isAjax()) {
			$moduleModelList = \App\Modules\Settings\PickListDependency\Models\Module::getPicklistSupportedModules();
			$forModule = $request->get('formodule');

			$viewer = $this->getViewer($request);
			$viewer->assign('PICKLIST_MODULES_LIST', $moduleModelList);
			$viewer->assign('FOR_MODULE', $forModule);

			$viewer = $this->getViewer($request);
			$this->initializeListViewContents($request, $viewer);
			$viewer->view('ListViewHeader.tpl', $request->getModule(false));
		}
		parent::process($request);
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
			'~libraries/jquery/malihu-custom-scrollbar/js/jquery.mCustomScrollbar.concat.min.js',
		);

		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		$headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);
		return $headerScriptInstances;
	}

	public function getHeaderCss(\App\Http\Vtiger_Request $request)
	{
		$headerCssInstances = parent::getHeaderCss($request);

		$cssFileNames = array(
			'~libraries/jquery/malihu-custom-scrollbar/css/jquery.mCustomScrollbar.css',
		);
		$cssInstances = $this->checkAndConvertCssStyles($cssFileNames);
		$headerCssInstances = array_merge($headerCssInstances, $cssInstances);

		return $headerCssInstances;
	}
}
