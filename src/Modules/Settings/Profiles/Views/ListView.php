<?php

namespace App\Modules\Settings\Profiles\Views;


/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * *********************************************************************************** */

class ListView extends \App\Modules\Settings\Vtiger\Views\Index
{

	public function getBreadcrumbTitle(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		return \App\Runtime\Vtiger_Language_Handler::translate('LBL_VIEW_LIST', $moduleName);
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		$qualifiedModuleName = $request->getModule(false);

		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('QUALIFIED_MODULE', $qualifiedModuleName);
		$viewer->assign('ALL_RECORDS', \App\Modules\Settings\Profiles\Models\Record::getAll());
		$viewer->assign('USER_MODEL', \App\Modules\Users\Models\Record::getCurrentUserModel());

		$viewer->view('ListView.tpl', $qualifiedModuleName);
	}

	/**
	 * Function to get the list of Script models to be included
	 * @param \App\Http\Vtiger_Request $request
	 * @return <Array> - List of Vtiger_JsScript_Model instances
	 */
	public function getFooterScripts(\App\Http\Vtiger_Request $request)
	{
		$headerScriptInstances = parent::getFooterScripts($request);
		$moduleName = $request->getModule();

		$jsFileNames = array(
			'modules.Settings.Vtiger.resources.Edit',
			"modules.Settings.$moduleName.resources.List"
		);

		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		$headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);
		return $headerScriptInstances;
	}
}
