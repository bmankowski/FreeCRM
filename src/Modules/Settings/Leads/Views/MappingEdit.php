<?php

namespace App\Modules\Settings\Leads\Views;


/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * ********************************************************************************** */


class MappingEdit extends \App\Modules\Settings\Base\Views\Index
{

	public function process(\App\Http\Vtiger_Request $request)
	{
		$qualifiedModuleName = $request->getModule(false);
		$viewer = $this->getViewer($request);

		$viewer->assign('MODULE_MODEL', \App\Modules\Settings\Leads\Models\Mapping::getInstance(true));
		$viewer->assign('LEADS_MODULE_MODEL', \App\Modules\Settings\Leads\Models\Module::getInstance('Leads'));
		$viewer->assign('ACCOUNTS_MODULE_MODEL', \App\Modules\Settings\Leads\Models\Module::getInstance('Accounts'));

		$viewer->assign('QUALIFIED_MODULE', $qualifiedModuleName);
		$viewer->view('LeadMappingEdit.tpl', $qualifiedModuleName);
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
			"modules.Settings.$moduleName.resources.LeadMapping"
		);

		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		$headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);
		return $headerScriptInstances;
	}
}
