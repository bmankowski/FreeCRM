<?php

namespace App\Modules\Settings\Mail\Views;


/* +***********************************************************************************************************************************
 * The contents of this file are subject to the YetiForce Public License Version 1.1 (the "License"); you may not use this file except
 * in compliance with the License.
 * Software distributed under the License is distributed on an "AS IS" basis, WITHOUT WARRANTY OF ANY KIND, either express or implied.
 * See the License for the specific language governing rights and limitations under the License.
 * The Original Code is YetiForce.
 * The Initial Developer of the Original Code is YetiForce. Portions created by YetiForce are Copyright (C) www.yetiforce.com. 
 * All Rights Reserved.
 * *********************************************************************************************************************************** */

class Autologin extends \App\Modules\Settings\Base\Views\Index
{

	/**
	 * Process
	 * @param \App\Http\Vtiger_Request $request
	 */
	public function process(\App\Http\Vtiger_Request $request)
	{
		$qualifiedModuleName = $request->getModule(false);

		$viewer = $this->getViewer($request);
		$viewer->assign('MODULE_MODEL', \App\Modules\Settings\Mail\Models\Autologin::getInstance());
		$viewer->assign('ERROR_MESSAGE', $request->get('errorMessage'));
		$viewer->assign('QUALIFIED_MODULE', $qualifiedModuleName);
		
		// Prepare Mail AutologinContent-specific data for AutologinContent template
		$this->prepareMailAutologinData($viewer);
		
		if ($request->isAjax()) {
			$viewer->view('AutologinContent.tpl', $qualifiedModuleName);
		} else {
			$viewer->view('AutologinIndex.tpl', $qualifiedModuleName);
		}
	}
	
	/**
	 * Prepare data for Mail AutologinContent template
	 * Moves function calls from template to controller for better MVC separation
	 */
	protected function prepareMailAutologinData($viewer)
	{
		$viewer->assign('ALL_ACTIVEUSER_LIST', \App\Fields\Owner::getInstance()->getAccessibleUsers());
		$viewer->assign('AUTOLOGIN_CONFIG', \App\Modules\Settings\Mail\Models\Config::getConfig('autologin'));
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

		$jsFileNames = [
			"modules.Settings.$moduleName.resources.Autologin"
		];

		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		$headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);
		return $headerScriptInstances;
	}
}
