<?php

namespace App\Modules\Settings\Users\Views;


/* +***********************************************************************************************************************************
 * The contents of this file are subject to the YetiForce Public License Version 1.1 (the "License"); you may not use this file except
 * in compliance with the License.
 * Software distributed under the License is distributed on an "AS IS" basis, WITHOUT WARRANTY OF ANY KIND, either express or implied.
 * See the License for the specific language governing rights and limitations under the License.
 * The Original Code is YetiForce.
 * The Initial Developer of the Original Code is YetiForce. Portions created by YetiForce are Copyright (C) www.yetiforce.com. 
 * All Rights Reserved.
 * *********************************************************************************************************************************** */

class Colors extends \App\Modules\Settings\Base\Views\Index
{

	public function process(\App\Http\Vtiger_Request $request)
	{
		$tablesAll = \App\Modules\Users\Models\Colors::getTablesAll();
		$moduleName = $request->getModule();
		$qualifiedModuleName = $request->getModule(false);
		$moduleModel = \App\Modules\Settings\Calendar\Models\Module::getInstance($qualifiedModuleName);
		$viewer = $this->getViewer($request);
		$viewer->assign('MODULE_MODEL', $moduleModel);
		$viewer->assign('TABLES_ALL', $tablesAll);
		$viewer->assign('QUALIFIED_MODULE', $qualifiedModuleName);
		$viewer->assign('MODULE', $moduleName);
		
		// Prepare Colors-specific data for ColorsContent template
		$this->prepareColorsData($viewer, $tablesAll);
		
		// Add AJAX detection for MainLayout conversion
		if ($request->isAjax()) {
			// AJAX request - return content only
			$viewer->view('ColorsContent.tpl', $qualifiedModuleName);
		} else {
			// Initial page load - return full page with MainLayout
			$viewer->view('ColorsIndex.tpl', $qualifiedModuleName);
		}
	}
	
	/**
	 * Prepare data for ColorsContent template
	 * Moves function calls from templates to controller for better MVC separation
	 */
	protected function prepareColorsData($viewer, $tablesAll)
	{
		// Prepare color data
		$viewer->assign('USER_COLORS', \App\Modules\Users\Models\Colors::getUserColors());
		$viewer->assign('GROUP_COLORS', \App\Modules\Users\Models\Colors::getGroupColors());
		$viewer->assign('MODULE_COLORS', \App\Modules\Users\Models\Colors::getModulesColors());
		
		// Prepare module names and field values for tables
		$moduleNames = [];
		$fieldValues = [];
		foreach ($tablesAll as $process => $elements) {
			foreach ($elements as $item) {
				if (empty($item)) {
					continue;
				}
				$moduleNames[$item['tabid']] = \App\Utils\ModuleUtils::getModuleName($item['tabid']);
				$fieldValues[$item['fieldname']] = \App\Modules\Users\Models\Colors::getValuesFromField($item['fieldname']);
			}
		}
		$viewer->assign('MODULE_NAMES', $moduleNames);
		$viewer->assign('FIELD_VALUES', $fieldValues);
	}

	public function getFooterScripts(\App\Http\Vtiger_Request $request)
	{
		$headerScriptInstances = parent::getFooterScripts($request);
		$moduleName = $request->getModule();
		$jsFileNames = array(
			"modules.Settings.$moduleName.resources.Colors",
			'~libraries/jquery/colorpicker/js/colorpicker.js'
		);
		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		$headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);
		return $headerScriptInstances;
	}

	public function getHeaderCss(\App\Http\Vtiger_Request $request)
	{
		$headerCssInstances = parent::getHeaderCss($request);
		$cssFileNames = array(
			'~libraries/jquery/colorpicker/css/colorpicker.css'
		);
		$cssInstances = $this->checkAndConvertCssStyles($cssFileNames);
		$headerCssInstances = array_merge($headerCssInstances, $cssInstances);
		return $headerCssInstances;
	}
}
