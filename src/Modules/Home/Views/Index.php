<?php

namespace App\Modules\Home\Views;

/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * ********************************************************************************** */


use App\Http\Vtiger_Request;
class Index extends \App\Modules\Base\Views\Index
{

	public function preProcess(\App\Http\Vtiger_Request $request, $display = true)
	{
		parent::preProcess($request, false);
		$this->assignDashboardData($request);
	}

	protected function assignDashboardData(\App\Http\Vtiger_Request $request)
	{
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		$currentDashboard = $request->get('dashboardId');
		if (empty($currentDashboard)) {
			$currentDashboard = \App\Modules\Settings\WidgetsManagement\Models\Module::getDefaultDashboard();
		}
		$dashBoardModel = \App\Modules\Base\Models\DashBoard::getInstance($moduleName);
		$dashBoardModel->set('dashboardId', $currentDashboard);
		
		// Get module model and check permissions
		$moduleModel = \App\Modules\Base\Models\Module::getInstance($moduleName);
		$userPrivilegesModel = \App\Modules\Users\Models\Privileges::getCurrentUserPrivilegesModel();
		
		if (!$moduleModel) {
			$permission = false;
			$widgets = [];
		} else {
			$permission = $userPrivilegesModel->hasModulePermission($moduleModel->getId());
		}
		
		if ($permission) {
			$dashBoardModel->verifyDashboard($moduleName);
			$widgets = $dashBoardModel->getDashboards();
		} else {
			$widgets = [];
		}

		$modulesWithWidget = \App\Modules\Base\Models\DashBoard::getModulesWithWidgets($moduleName, $currentDashboard);
		
		$viewer->assign('CURRENT_DASHBOARD', $currentDashboard);
		$viewer->assign('DASHBOARD_TYPES', \App\Modules\Settings\WidgetsManagement\Models\Module::getDashboardTypes());
		$viewer->assign('USER_PRIVILEGES_MODEL', $userPrivilegesModel);
		$viewer->assign('MODULE_PERMISSION', $permission);
		$viewer->assign('WIDGETS', $widgets);
		$viewer->assign('MODULE_NAME', $moduleName);
		$viewer->assign('MODULE_MODEL', $moduleModel);
		$viewer->assign('MODULES_WITH_WIDGET', $modulesWithWidget);
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();

		$viewer->view('Index.tpl', $moduleName);
	}

	/**
	 * Function to get the list of Script models to be included
	 * @param \App\Http\Vtiger_Request $request
	 * @return <Array> - List of \App\Modules\Base\Models\JsScript instances
	 */
	public function getFooterScripts(\App\Http\Vtiger_Request $request)
	{
		$headerScriptInstances = parent::getFooterScripts($request);
		$moduleName = $request->getModule();

		$jsFileNames = array(
			'~libraries/jquery/gridster/jquery.gridster.min.js',
			'~libraries/jquery/flot/jquery.flot.min.js',
			'~libraries/jquery/flot/jquery.flot.pie.min.js',
			'~libraries/jquery/flot/jquery.flot.stack.min.js',
			'~libraries/jquery/jqplot/jquery.jqplot.min.js',
			'~libraries/jquery/jqplot/plugins/jqplot.canvasTextRenderer.min.js',
			'~libraries/jquery/jqplot/plugins/jqplot.canvasAxisTickRenderer.min.js',
			'~libraries/jquery/jqplot/plugins/jqplot.pieRenderer.min.js',
			'~libraries/jquery/jqplot/plugins/jqplot.barRenderer.min.js',
			'~libraries/jquery/jqplot/plugins/jqplot.categoryAxisRenderer.min.js',
			'~libraries/jquery/jqplot/plugins/jqplot.pointLabels.min.js',
			'~libraries/jquery/jqplot/plugins/jqplot.canvasAxisLabelRenderer.min.js',
			'~libraries/jquery/jqplot/plugins/jqplot.funnelRenderer.min.js',
			'~libraries/jquery/jqplot/plugins/jqplot.barRenderer.min.js',
			'~libraries/jquery/jqplot/plugins/jqplot.logAxisRenderer.min.js',
			'modules.Base.resources.DashBoard',
			'modules.' . $moduleName . '.resources.Index',
			'modules.Base.resources.dashboards.Widget',
			'~libraries/fullcalendar/moment.min.js',
			'~libraries/fullcalendar/fullcalendar.js',
			'~libraries/jquery/boxslider/jqueryBxslider.js'
		);

		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		$headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);
		return $headerScriptInstances;
	}

	/**
	 * Function to get the list of Css models to be included
	 * @param \App\Http\Vtiger_Request $request
	 * @return <Array> - List of \App\Modules\Base\Models\CssScript instances
	 */
	public function getHeaderCss(\App\Http\Vtiger_Request $request)
	{
		$parentHeaderCssScriptInstances = parent::getHeaderCss($request);

		$headerCss = array(
			'~libraries/jquery/gridster/jquery.gridster.min.css',
			'~libraries/jquery/jqplot/jquery.jqplot.min.css',
			'~libraries/fullcalendar/fullcalendar.min.css',
			'~libraries/fullcalendar/fullcalendarCRM.css'
		);
		$cssScripts = $this->checkAndConvertCssStyles($headerCss);
		$headerCssScriptInstances = array_merge($parentHeaderCssScriptInstances, $cssScripts);
		return $headerCssScriptInstances;
	}
}
