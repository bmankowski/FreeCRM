<?php

namespace App\Modules\Settings\ModTracker\Views;


/* +***********************************************************************************************************************************
 * The contents of this file are subject to the YetiForce Public License Version 1.1 (the "License"); you may not use this file except
 * in compliance with the License.
 * Software distributed under the License is distributed on an "AS IS" basis, WITHOUT WARRANTY OF ANY KIND, either express or implied.
 * See the License for the specific language governing rights and limitations under the License.
 * The Original Code is YetiForce.
 * The Initial Developer of the Original Code is YetiForce. Portions created by YetiForce are Copyright (C) www.yetiforce.com. 
 * All Rights Reserved.
 * *********************************************************************************************************************************** */

class ListView extends \App\Modules\Settings\Base\Views\Index
{

	public function process(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$qualifiedModuleName = $request->getModule(false);
		$moduleModel = new \App\Modules\Settings\ModTracker\Models\Module();
		$viewer = $this->getViewer($request);
		$viewer->assign('MODULE_MODEL', $moduleModel);
		$viewer->assign('QUALIFIED_MODULE', $qualifiedModuleName);
		$viewer->assign('MODULE', $moduleName);
		
		if ($request->isAjax()) {
			// AJAX request - return content only
			$viewer->view('ListContent.tpl', $qualifiedModuleName);
		} else {
			// Initial page load - return full page with MainLayout
			$viewer->view('ListViewIndex.tpl', $qualifiedModuleName);
		}
	}

	public function getFooterScripts(\App\Http\Vtiger_Request $request)
	{
		$footerScripts = parent::getFooterScripts($request);
		$moduleName = $request->getModule();

		$jsFileNames = [
			'modules.Settings.Vtiger.resources.Index',
			"modules.Settings.$moduleName.resources.ModTracker",
		];

		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		return array_merge($footerScripts, $jsScriptInstances);
	}
}
