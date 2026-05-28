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

class Widget extends \App\Modules\Settings\Base\Views\Index
{

	public function process(\App\Http\Vtiger_Request $request)
	{
		$mode = $request->getMode();
		if ($mode) {
			$this->$mode($request);
		} else {
			$this->createStep1($request);
		}
	}

	public function createStep1(\App\Http\Vtiger_Request $request)
	{
		$viewer = $this->getViewer($request);
		$sourceModule = $request->get('mod');
		$moduleName = $request->getModule();
		$qualifiedModuleName = $request->getModule(false);
		$moduleModel = \App\Modules\Settings\Widgets\Models\Module::getInstance($qualifiedModuleName);
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('MODULE_MODEL', $moduleModel);
		$viewer->assign('SOUNRCE_MODULE', $sourceModule);
		$viewer->assign('QUALIFIED_MODULE', $qualifiedModuleName);
		$viewer->view('WidgetList.tpl', $qualifiedModuleName);
	}

	public function createStep2(\App\Http\Vtiger_Request $request)
	{
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		$qualifiedModuleName = $request->getModule(false);
		$type = $request->get('type');
		$tabId = $request->get('tabId');
		$moduleModel = \App\Modules\Settings\Widgets\Models\Module::getInstance($qualifiedModuleName);
		$RelatedModule = $moduleModel->getRelatedModule($tabId);
		
		// Get source module name for widget lookup
		$sourceModuleName = \App\Utils\ModuleUtils::getModuleName($tabId);
		
		$viewer->assign('TYPE', $type);
		$viewer->assign('SOURCE', $tabId);
		$viewer->assign('WID', '');
		$viewer->assign('WIDGETINFO', ['data' => [
				'limit' => 5, 'relatedmodule' => '', 'columns' => '', 'action' => '', 'switchHeader' => '', 'filter' => '', 'checkbox' => ''
			], 'nomargin' => '', 'label' => ''
		]);
		$viewer->assign('SOURCEMODULE', $sourceModuleName);
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('MODULE_MODEL', $moduleModel);
		$viewer->assign('QUALIFIED_MODULE', $qualifiedModuleName);
		$viewer->assign('RELATEDMODULES', $RelatedModule);
		$viewer->assign('PRIVILEGESMODEL', \App\Modules\Users\Models\Privileges::getCurrentUserPrivilegesModel());
		
		// Try to find widget class using Loader - first in source module, then in Base
		$widgetClassName = \App\Core\Loader::getComponentClassName('Widget', $type, $sourceModuleName, false);
		if (!$widgetClassName) {
			$widgetClassName = \App\Core\Loader::getComponentClassName('Widget', $type, 'Base', false);
		}
		
		$tplName = 'BasicConfig';
		
		if ($widgetClassName && class_exists($widgetClassName)) {
			$widgetInstance = new $widgetClassName();
			$tplName = $widgetInstance->getConfigTplName();
			
			// Check if config template exists in source module's widgets directory
			$sourceModuleTplPath = "layouts/basic/modules/{$sourceModuleName}/widgets/{$tplName}.tpl";
			if (file_exists(ROOT_DIRECTORY . '/' . $sourceModuleTplPath)) {
				$viewer->view("widgets/{$tplName}.tpl", $sourceModuleName);
				return;
			}
		}
		
		// Fallback to Base widgets config template
		$viewer->view("widgets/{$tplName}.tpl", 'Base');
	}

	public function edit(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$qualifiedModuleName = $request->getModule(false);
		$wid = $request->get('id');
		$moduleModel = \App\Modules\Settings\Widgets\Models\Module::getInstance($qualifiedModuleName);
		$WidgetInfo = $moduleModel->getWidgetInfo($wid);
		$RelatedModule = $moduleModel->getRelatedModule($WidgetInfo['tabid']);
		$type = $WidgetInfo['type'];
		$sourceModuleName = \App\Utils\ModuleUtils::getModuleName($WidgetInfo['tabid']);
		
		$viewer = $this->getViewer($request);
		$viewer->assign('SOURCE', $WidgetInfo['tabid']);
		$viewer->assign('SOURCEMODULE', $sourceModuleName);
		$viewer->assign('WID', $wid);
		$viewer->assign('WIDGETINFO', $WidgetInfo);
		$viewer->assign('TYPE', $type);
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('MODULE_MODEL', $moduleModel);
		$viewer->assign('QUALIFIED_MODULE', $qualifiedModuleName);
		$viewer->assign('RELATEDMODULES', $RelatedModule);
		
		// Try to find widget class using Loader - first in source module, then in Base
		$widgetClassName = \App\Core\Loader::getComponentClassName('Widget', $type, $sourceModuleName, false);
		if (!$widgetClassName) {
			$widgetClassName = \App\Core\Loader::getComponentClassName('Widget', $type, 'Base', false);
		}
		
		$tplName = 'BasicConfig';
		
		if ($widgetClassName && class_exists($widgetClassName)) {
			$widgetInstance = new $widgetClassName();
			$tplName = $widgetInstance->getConfigTplName();
			
			// Check if config template exists in source module's widgets directory
			$sourceModuleTplPath = "layouts/basic/modules/{$sourceModuleName}/widgets/{$tplName}.tpl";
			if (file_exists(ROOT_DIRECTORY . '/' . $sourceModuleTplPath)) {
				$viewer->view("widgets/{$tplName}.tpl", $sourceModuleName);
				return;
			}
		}
		
		// Fallback to Base widgets config template
		$viewer->view("widgets/{$tplName}.tpl", 'Base');
	}
}
