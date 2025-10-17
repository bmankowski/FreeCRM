<?php

namespace App\Modules\Settings\WidgetsManagement\Views;



/**
 * Form to add/edit dashboard
 * @package YetiForce.view
 * @license licenses/License.html
 * @author Tomasz Kur <t.kur@yetiforce.com>
 */
class DashboardType extends \App\Modules\Settings\Vtiger\Views\BasicModal
{

	public function process(\App\Http\Vtiger_Request $request)
	{
		$dashboardId = $request->get('dashboardId');
		$dashboardInfo = \App\Modules\Settings\WidgetsManagement\Models\Module::getDashboardInfo($dashboardId);
		$moduleName = $request->getModule(false);
		$viewer = $this->getViewer($request);
		$viewer->assign('DASHBOARD_ID', $dashboardId);
		$viewer->assign('DASHBOARD_NAME', $dashboardInfo['name']);
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('MODULE_NAME', $request->getModule());
		$viewer->view('DashboardType.tpl', $moduleName);
	}
}
