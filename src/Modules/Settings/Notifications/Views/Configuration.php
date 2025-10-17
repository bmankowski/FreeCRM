<?php

namespace App\Modules\Settings\Notifications\Views;



/**
 * Configuration notifications
 * @package YetiForce.Settings.View
 * @license licenses/License.html
 * @author Tomasz Kur <t.kur@yetiforce.com>
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 */
class Configuration extends \App\Modules\Settings\Vtiger\Views\Index
{

	/**
	 * Function gets module settings
	 * @param \App\Http\Vtiger_Request $request
	 */
	public function process(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$srcModule = $request->get('srcModule');
		$modules = \App\Modules\Vtiger\Models\Watchdog::getSupportedModules();
		if (!$request->has('srcModule')) {
			reset($modules);
			$srcModule = key($modules);
		}
		$viewer = $this->getViewer($request);
		$viewer->assign('WATCHDOG_MODULE', \App\Modules\Vtiger\Models\Watchdog::getInstance($srcModule));
		$viewer->assign('SELECTED_MODULE', $srcModule);
		$viewer->assign('SUPPORTED_MODULES', $modules);
		$viewer->view('Configuration.tpl', $request->getModule(false));
	}

	/**
	 * Function to get the list of Script models to be included
	 * @param \App\Http\Vtiger_Request $request
	 * @return array - List of Vtiger_JsScript_Model instances
	 */
	public function getFooterScripts(\App\Http\Vtiger_Request $request)
	{
		$headerScriptInstances = parent::getFooterScripts($request);
		$moduleName = $request->getModule();
		$jsFileNames = [
			"modules.Settings.$moduleName.resources.Configuration",
			'~libraries/jquery/datatables/media/js/jquery.dataTables.min.js',
			'~libraries/jquery/datatables/plugins/integration/bootstrap/3/dataTables.bootstrap.min.js'
		];
		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		$headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);
		return $headerScriptInstances;
	}
}
