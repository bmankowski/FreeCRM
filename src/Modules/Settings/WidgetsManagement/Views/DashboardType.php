<?php

namespace FreeCRM\Modules\Settings\WidgetsManagement\Views;
use FreeCRM\Modules\Settings\WidgetsManagement\Models\Module as Settings_WidgetsManagement_Module_Model;



/**
 * Form to add/edit dashboard
 * @package YetiForce.view
 * @license licenses/License.html
 * @author Tomasz Kur <t.kur@yetiforce.com>
 */
class DashboardType extends \FreeCRM\Modules\Settings\Vtiger\Views\BasicModal
{

	public function process(\FreeCRM\Http\Vtiger_Request $request)
	{
		$dashboardId = $request->get('dashboardId');
		$dashboardInfo = \FreeCRM\Modules\Settings\WidgetsManagement\Models\Module::getDashboardInfo($dashboardId);
		$moduleName = $request->getModule(false);
		$viewer = $this->getViewer($request);
		$viewer->assign('DASHBOARD_ID', $dashboardId);
		$viewer->assign('DASHBOARD_NAME', $dashboardInfo['name']);
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('MODULE_NAME', $request->getModule());
		$viewer->view('DashboardType.tpl', $moduleName);
	}
}
