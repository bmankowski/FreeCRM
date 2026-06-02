<?php

namespace App\Modules\Settings\SharingAccess\Views;
use App\Modules\Settings\SharingAccessModels\Action;


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

	public function process(\App\Http\Vtiger_Request $request)
	{
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		$qualifiedModuleName = $request->getModule(false);

		$viewer->assign('ALL_MODULES', \App\Modules\Settings\SharingAccess\Models\Module::getAll(true));
		$viewer->assign('ALL_ACTIONS', \App\Modules\Settings\SharingAccess\Models\Action::getAll());
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('USER_MODEL', $request->getUser());
		$dependentModules = \App\Modules\Settings\SharingAccess\Models\Module::getDependentModules();
		$viewer->assign('DEPENDENT_MODULES', $dependentModules);
		
		// Prepare SharingAccess-specific data for IndexContent template
		$this->prepareSharingAccessIndexData($viewer, $dependentModules);

		// Check if this is an AJAX request - if so, return only content without MainLayout
		if ($request->isAjax()) {
			$viewer->view('IndexContent.tpl', $qualifiedModuleName);
		} else {
			$viewer->view('Index.tpl', $qualifiedModuleName);
		}
	}
	
	/**
	 * Prepare data for SharingAccess IndexContent template
	 * Moves function calls from template to controller for better MVC separation
	 */
	protected function prepareSharingAccessIndexData($viewer, $dependentModules)
	{
		$viewer->assign('DEPENDENT_MODULES_JSON', \App\Utils\Json::encode($dependentModules));
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
			"modules.Settings.$moduleName.resources.Index"
		);

		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		$headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);
		return $headerScriptInstances;
	}
}
