<?php

namespace App\Modules\Settings\Widgets\Views;
use App\Modules\Settings\Widgets\Models\Module;


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
		$moduleName = $request->getModule();
		$qualifiedModuleName = $request->getModule(false);
		$source = $request->get('source');
		$sourceModule = $request->get('sourceModule');
		if ($sourceModule != '')
			$source = \vtlib\Functions:: getModuleId($sourceModule);
		if ($source == '')
			$source = 6;
		$moduleModel = \App\Modules\Settings\Widgets\Models\Module::getInstance($qualifiedModuleName);
		$RelatedModule = $moduleModel->getRelatedModule($source);
		$viewer = $this->getViewer($request);
		$viewer->assign('MODULE_MODEL', $moduleModel);
		$viewer->assign('SOURCE', $source);
		$viewer->assign('SOURCEMODULE', \App\Utils\ModuleUtils::getModuleName($source));
		$widgets = $moduleModel->getWidgets($source);
		$viewer->assign('WIDGETS', $widgets);
		$viewer->assign('RELATEDMODULES', $RelatedModule);
		$viewer->assign('FILTERS', json_encode($moduleModel->getFiletrs($RelatedModule)));
		$viewer->assign('CHECKBOXS', json_encode($moduleModel->getCheckboxs($RelatedModule)));
		$viewer->assign('SWITCHES_HEADER', json_encode($moduleModel->getHeaderSwitch()));
		$viewer->assign('QUALIFIED_MODULE', $qualifiedModuleName);
		$viewer->assign('MODULE', $moduleName);
		
		// Prepare Widgets-specific data for IndexContent template
		$this->prepareWidgetsData($viewer, $widgets);
		
		if ($request->isAjax()) {
			// AJAX handling - return only contents without MainLayout
			$viewer->view('IndexContent.tpl', $qualifiedModuleName);
		} else {
			// Full page rendering - use Index.tpl which extends MainLayout
			$viewer->view('Index.tpl', $qualifiedModuleName);
		}
	}
	
	/**
	 * Prepare data for Widgets IndexContent template
	 * Moves function calls from templates to controller for better MVC separation
	 */
	protected function prepareWidgetsData($viewer, $widgets)
	{
		// Prepare module names for widgets
		$widgetModuleNames = [];
		foreach ($widgets as $column => $widgetColumn) {
			foreach ($widgetColumn as $key => $widget) {
				if (empty($widget['label']) && isset($widget['data']['relatedmodule'])) {
					$moduleName = \App\Utils\ModuleUtils::getModuleName($widget['data']['relatedmodule']);
					$widgetModuleNames[$column][$key] = $moduleName;
				}
			}
		}
		$viewer->assign('WIDGET_MODULE_NAMES', $widgetModuleNames);
	}

	public function getHeaderCss(\App\Http\Vtiger_Request $request)
	{
		$headerCssInstances = parent::getHeaderCss($request);
		$moduleName = $request->getModule();
		$cssFileNames = [
			"modules.Settings.$moduleName.resources.$moduleName",
		];
		$cssInstances = $this->checkAndConvertCssStyles($cssFileNames);
		$headerCssInstances = array_merge($headerCssInstances, $cssInstances);

		return $headerCssInstances;
	}

	public function getFooterScripts(\App\Http\Vtiger_Request $request)
	{
		$headerScriptInstances = parent::getFooterScripts($request);
		$moduleName = $request->getModule();
		$jsFileNames = array(
			"modules.Settings.$moduleName.resources.$moduleName"
		);
		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		$headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);
		return $headerScriptInstances;
	}
}
