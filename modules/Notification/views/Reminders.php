<?php
/**
 * Notifications reminders
 * @package YetiForce.View
 * @license licenses/License.html
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 */

/**
 * Class for Notifications reminders
 */

use FreeCRM\Http\Vtiger_Request;
class Notification_Reminders_View extends Vtiger_IndexAjax_View
{

	/**
	 * Process
	 * @param Vtiger_Request $request
	 */
	public function process(\FreeCRM\Http\Vtiger_Request $request)
	{
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		$moduleModel = Vtiger_Module_Model::getInstance($moduleName);
		$entries = $moduleModel->getEntries(\AppConfig::module($moduleName, 'MAX_NUMBER_NOTIFICATIONS'));
		$colors = ['PLL_SYSTEM' => '#FF9800', 'PLL_USERS' => '#1baee2'];
		$viewer->assign('RECORDS', $entries);
		$viewer->assign('COLORS', $colors);
		$viewer->view('Reminders.tpl', $moduleName);
	}
}
