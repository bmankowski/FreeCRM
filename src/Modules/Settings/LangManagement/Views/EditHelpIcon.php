<?php

namespace App\Modules\Settings\LangManagement\Views;


/* +***********************************************************************************************************************************
 * The contents of this file are subject to the YetiForce Public License Version 1.1 (the "License"); you may not use this file except
 * in compliance with the License.
 * Software distributed under the License is distributed on an "AS IS" basis, WITHOUT WARRANTY OF ANY KIND, either express or implied.
 * See the License for the specific language governing rights and limitations under the License.
 * The Original Code is YetiForce.
 * The Initial Developer of the Original Code is YetiForce. Portions created by YetiForce are Copyright (C) www.yetiforce.com. 
 * All Rights Reserved.
 * *********************************************************************************************************************************** */

class EditHelpIcon extends \App\Modules\Settings\Base\Views\Index
{

	public function preProcess(\App\Http\Vtiger_Request $request, $display = true)
	{
		
	}

	public function postProcess(\App\Http\Vtiger_Request $request)
	{
		
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$qualifiedModuleName = $request->getModule(false);
		$lang = $request->get('lang');
		$mod = $request->get('mod');
		$ShowDifferences = $request->get('sd');
		$moduleModel = \App\Modules\Settings\LangManagement\Models\Module::getInstance($qualifiedModuleName);
		if ($lang != '' && $mod != '') {
			$data = $moduleModel->loadAllFieldsFromModule($lang, $mod, $ShowDifferences);
		}
		$Langs = $moduleModel->getLang();
		$viewer = $this->getViewer($request);
		// Prepare modules list for EditHelpIcon template - use adapter to match vtlib structure
		$allModules = \vtlib\Functions:: getAllModules(true, false, 0);
		$viewer->assign('ALL_MODULES', $allModules);
		$viewer->assign('MODULE_MODEL', $moduleModel);
		$viewer->assign('REQUEST', $request);
		$viewer->assign('LANGS', $Langs);
		$viewer->assign('DATA', $data);
		$viewer->assign('LANGS', $Langs);
		$viewer->assign('SD', $ShowDifferences);
		$viewer->assign('QUALIFIED_MODULE', $qualifiedModuleName);
		$viewer->assign('MODULE', $moduleName);
		$viewer->view('EditHelpIcon.tpl', $qualifiedModuleName);
	}
}
