<?php

namespace App\Modules\Home\Views;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * *********************************************************************************** */


use App\Http\Vtiger_Request;

/**
 * Backward compatibility redirect for Home DashBoard view
 * Home now uses Index view instead of DashBoard view
 * This class redirects old DashBoard URLs to Index view
 */
class DashBoard extends \App\Modules\Base\Views\DashBoard
{

	public function preProcess(\App\Http\Vtiger_Request $request, $display = true)
	{
		// Redirect Home DashBoard to Index for backward compatibility
		$moduleName = $request->getModule();
		if ($moduleName === 'Home') {
			$dashboardId = $request->get('dashboardId');
			$redirectUrl = 'index.php?module=Home&view=Index';
			if (!empty($dashboardId)) {
				$redirectUrl .= '&dashboardId=' . $dashboardId;
			}
			header('Location: ' . $redirectUrl);
			exit;
		}
		
		// For other modules, use normal DashBoard behavior
		parent::preProcess($request, false);
		
		// Assign Home-specific dashboard data
		$currentDashboard = $request->get('dashboardId');
		if (empty($currentDashboard)) {
			$currentDashboard = \App\Modules\Settings\WidgetsManagement\Models\Module::getDefaultDashboard();
		}
		$viewer = $this->getViewer($request);
		$modulesWithWidget = \App\Modules\Base\Models\DashBoard::getModulesWithWidgets($moduleName, $currentDashboard);
		$viewer->assign('MODULES_WITH_WIDGET', $modulesWithWidget);
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
