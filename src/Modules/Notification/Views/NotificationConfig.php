<?php

namespace App\Modules\Notification\Views;

/**
 * Show modal with configuration
 * @package YetiForce.View
 * @license licenses/License.html
 * @author Tomasz Kur <t.kur@yetiforce.com>
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 */

use App\Http\Vtiger_Request;
class NotificationConfig  extends \App\Modules\Base\Views\Index
{

	/**
	 * Function get modal size
	 * @param \App\Http\Vtiger_Request $request
	 * @return string
	 */
	public function getSize(\App\Http\Vtiger_Request $request)
	{
		return 'modal-lg';
	}

	/**
	 * Function gets module settings
	 * @param \App\Http\Vtiger_Request $request
	 */
	public function process(\App\Http\Vtiger_Request $request)
	{
		parent::preProcess($request);
		$moduleName = $request->getModule();
		$moduleList = \App\Modules\Base\Models\Watchdog::getSupportedModules();
		foreach ($moduleList as $tabId => &$module) {
			if (!\App\Privilege::isPermitted($module->getName(), 'WatchingModule')) {
				unset($moduleList[$tabId]);
			}
		}
		$watchingModules = \App\Modules\Base\Models\Watchdog::getWatchingModules();
		$scheduleData = \App\Modules\Base\Models\Watchdog::getWatchingModulesSchedule();
		$selectedAllModules = count($moduleList) === count($watchingModules) ? true : false;
		$selectedAllSendNotice = count($moduleList) === count($scheduleData['modules']) ? true : false;
		$viewer = $this->getViewer($request);
		$viewer->assign('MODULE_LIST', $moduleList);
		$viewer->assign('WATCHING_MODEL', \App\Modules\Base\Models\Watchdog::getInstance($moduleName));
		$viewer->assign('WATCHING_MODULES', $watchingModules);
		$viewer->assign('SELECT_ALL_MODULES', $selectedAllModules);
		$viewer->assign('IS_ALL_EMAIL_NOTICE', $selectedAllSendNotice);
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('FREQUENCY', $scheduleData['frequency']);
		$viewer->assign('SCHEDULE_DATA', $scheduleData);
		$viewer->assign('CRON_INFO', \vtlib\Cron::getInstance('LBL_SEND_NOTIFICATIONS'));
		$viewer->view('NotificationConfig.tpl', $moduleName);
		parent::postProcess($request);
	}

	/**
	 * Function to get the list of Css models to be included
	 * @param \App\Http\Vtiger_Request $request
	 * @return array - List of \App\Modules\Base\Models\CssScript instances
	 */
	public function getModalScripts(\App\Http\Vtiger_Request $request)
	{
		$parentScriptInstances = parent::getModalScripts($request);
		$scripts = [
			'~libraries/jquery/datatables/media/js/jquery.dataTables.min.js',
			'~libraries/jquery/datatables/plugins/integration/bootstrap/3/dataTables.bootstrap.min.js'
		];
		$modalInstances = $this->checkAndConvertJsScripts($scripts);
		$scriptInstances = array_merge($modalInstances, $parentScriptInstances);
		return $scriptInstances;
	}
}
