<?php

namespace App\Modules\Notification\Views;

/**
 * Notifications reminders
 * @package YetiForce.View
 * @license licenses/License.html
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 */

/**
 * Class for Notifications reminders
 */

use App\Http\Vtiger_Request;
class Reminders  extends \App\Modules\Vtiger\Views\Index
{

	/**
	 * Process
	 * @param Vtiger_Request $request
	 */
	public function process(\App\Http\Vtiger_Request $request)
	{
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		$moduleModel = \App\Modules\Vtiger\Models\Module::getInstance($moduleName);
		$entries = $moduleModel->getEntries(\App\AppConfig::module($moduleName, 'MAX_NUMBER_NOTIFICATIONS'));
		$colors = ['PLL_SYSTEM' => '#FF9800', 'PLL_USERS' => '#1baee2'];
		$viewer->assign('RECORDS', $entries);
		$viewer->assign('COLORS', $colors);
		$viewer->view('Reminders.tpl', $moduleName);
	}
}
