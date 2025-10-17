<?php

namespace App\Modules\Home\Views;
use App\Modules\Settings\WidgetsManagement\Models\Module as Settings_WidgetsManagement_Module_Model;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * *********************************************************************************** */

use App\Modules\Vtiger\Views\DashBoard as VtigerDashBoard;

use App\Http\Vtiger_Request;
class DashBoard extends VtigerDashBoard
{

	public function preProcess(\App\Http\Vtiger_Request $request, $display = true)
	{
		parent::preProcess($request, false);
		$moduleName = $request->getModule();
		$currentDashboard = $request->get('dashboardId');
		if (empty($currentDashboard)) {
			$currentDashboard = \App\Modules\Settings\WidgetsManagement\Models\Module::getDefaultDashboard();
		}
		$viewer = $this->getViewer($request);
		$modulesWithWidget = \App\Modules\Vtiger\Models\DashBoard::getModulesWithWidgets($moduleName, $currentDashboard);
		$viewer->assign('MODULES_WITH_WIDGET', $modulesWithWidget);
		$this->preProcessDisplay($request);
	}

	public function getFooterScripts(\App\Http\Vtiger_Request $request)
	{
		$headerScriptInstances = parent::getFooterScripts($request);
		$moduleName = $request->getModule();

		$jsFileNames = array(
			'~libraries/jquery/boxslider/jqueryBxslider.js'
		);

		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		$headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);
		return $headerScriptInstances;
	}
}
