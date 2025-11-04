<?php

namespace App\Modules\Settings\DataAccess\Views;


/* +***********************************************************************************************************************************
 * The contents of this file are subject to the YetiForce Public License Version 1.1 (the "License"); you may not use this file except
 * in compliance with the License.
 * Software distributed under the License is distributed on an "AS IS" basis, WITHOUT WARRANTY OF ANY KIND, either express or implied.
 * See the License for the specific language governing rights and limitations under the License.
 * The Original Code is YetiForce.
 * The Initial Developer of the Original Code is YetiForce. Portions created by YetiForce are Copyright (C) www.yetiforce.com. 
 * All Rights Reserved.
 * *********************************************************************************************************************************** */

class Index extends \App\Modules\Settings\Base\Views\Index
{

	public function preProcess(\App\Http\Vtiger_Request $request, $display = true)
	{
		parent::preProcess($request);
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$qualifiedModuleName = $request->getModule(false);
		$moduleName = $request->getModule();

		$viewer = $this->getViewer($request);
		$viewer->assign('MODULE_NAME', $moduleName);
		$viewer->assign('QUALIFIED_MODULE', $qualifiedModuleName);
		$viewer->assign('DOC_TPL_LIST', \App\Modules\Settings\DataAccess\Models\Module::getDataAccessList());
		$viewer->assign('SUPPORTED_MODULE_MODELS', \App\Modules\Settings\DataAccess\Models\Module::getEntityModulesList());
		$viewer->assign('SETTINGS_MODULE_NAME', $qualifiedModuleName);
		$viewer->assign('DOCUMENT_LIST', $qualifiedModuleName);

		echo $viewer->view('Index.tpl', $qualifiedModuleName, true);
	}

	public function getFooterScripts(\App\Http\Vtiger_Request $request)
	{
		$headerScriptInstances = parent::getFooterScripts($request);
		$moduleName = $request->getModule();

		$jsFileNames = array(
			"modules.Settings.$moduleName.resources.Conditions"
		);

		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		$headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);
		return $headerScriptInstances;
	}
}
