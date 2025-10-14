<?php

namespace FreeCRM\Modules\Settings\PickListDependency\Views;


/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * ********************************************************************************** */

use FreeCRM\Modules\Settings\PickListDependency\Models\Module as Settings_PickListDependency_Module_Model;
class List extends \FreeCRM\Modules\Settings\Vtiger\Views\List
{

	public function preProcess(\FreeCRM\Http\Vtiger_Request $request, $display = true)
	{
		$moduleModelList = Settings_PickListDependency_Module_Model::getPicklistSupportedModules();
		$forModule = $request->get('formodule');
		$viewer = $this->getViewer($request);
		$viewer->assign('PICKLIST_MODULES_LIST', $moduleModelList);
		$viewer->assign('FOR_MODULE', $forModule);
		parent::preProcess($request, $display);
	}

	public function process(\FreeCRM\Http\Vtiger_Request $request)
	{
		if ($request->isAjax()) {
			$moduleModelList = Settings_PickListDependency_Module_Model::getPicklistSupportedModules();
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
	 * @param \FreeCRM\Http\Vtiger_Request $request
	 * @return <Array> - List of Vtiger_JsScript_Model instances
	 */
	public function getFooterScripts(\FreeCRM\Http\Vtiger_Request $request)
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

	public function getHeaderCss(\FreeCRM\Http\Vtiger_Request $request)
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
