<?php

namespace App\Modules\Settings\Roles\Views;


/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * ********************************************************************************** */

class Index extends \App\Modules\Settings\Base\Views\Index
{

	public function process(\App\Http\Vtiger_Request $request)
	{
		$viewer = $this->getViewer($request);
		$qualifiedModuleName = $request->getModule(false);
		$rootRole = \App\Modules\Settings\Roles\Models\Record::getBaseRole();
		$allRoles = \App\Modules\Settings\Roles\Models\Record::getAll();

		$viewer->assign('ROOT_ROLE', $rootRole);
		$viewer->assign('ROLES', $allRoles);
		$viewer->assign('VIEW', $request->get('view'));
		$viewer->assign('TYPE', $request->get('type'));
		
		// Check if this is an AJAX request - if so, return only content without MainLayout
		if ($request->isAjax()) {
			$viewer->view('IndexContent.tpl', $qualifiedModuleName);
		} else {
			$viewer->view('Index.tpl', $qualifiedModuleName);
		}
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
			'modules.Settings.Vtiger.resources.Index',
			"modules.Settings.$moduleName.resources.Roles",
			'modules.Settings.Vtiger.resources.Popup',
			"modules.Settings.$moduleName.resources.Popup",
			'libraries.jquery.jquery_windowmsg',
		);

		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		$headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);
		return $headerScriptInstances;
	}

	/**
	 * Function to get the list of Css models to be included
	 * @param \App\Http\Vtiger_Request $request
	 * @return array - List of StyleAsset instances
	 */
	public function getHeaderCss(\App\Http\Vtiger_Request $request)
	{
		$headerCssInstances = parent::getHeaderCss($request);
		$moduleName = $request->getModule();

		$cssFileNames = array(
			'libraries.jquery.jqTree.jqtree'
		);

		$cssStyleInstances = $this->checkAndConvertCssStyles($cssFileNames);
		$headerCssInstances = array_merge($headerCssInstances, $cssStyleInstances);
		return $headerCssInstances;
	}
}
