<?php

namespace App\Modules\Settings\Users\Views;



/**
 * Switch Users View Class
 * @package YetiForce.View
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */

class SwitchUsers extends \App\Modules\Settings\Base\Views\Index
{

	public function process(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$qualifiedModuleName = $request->getModule(false);
		$moduleModel = \App\Modules\Settings\Users\Models\Module::getInstance();
		$viewer = $this->getViewer($request);
		$viewer->assign('SWITCH_USERS', $moduleModel->getSwitchUsers());
		$viewer->assign('QUALIFIED_MODULE', $qualifiedModuleName);
		$viewer->assign('MODULE_MODEL', $moduleModel);
		$viewer->assign('MODULE', $moduleName);
		
		// Prepare SwitchUsers-specific data for SwitchUsersContent template
		$this->prepareSwitchUsersData($viewer);

		if ($request->isAjax()) {
			$viewer->view('SwitchUsersContent.tpl', $qualifiedModuleName);
		} else {
			$viewer->view('SwitchUsersIndex.tpl', $qualifiedModuleName);
		}
	}
	
	/**
	 * Prepare data for SwitchUsersContent template
	 * Moves function calls from template to controller for better MVC separation
	 */
	protected function prepareSwitchUsersData($viewer)
	{
		$viewer->assign('USERS', \App\Modules\Users\Models\Record::getAll());
		$viewer->assign('ROLES', \App\Modules\Settings\Roles\Models\Record::getAll());
	}

	public function getFooterScripts(\App\Http\Vtiger_Request $request)
	{
		$headerScriptInstances = parent::getFooterScripts($request);
		$moduleName = $request->getModule();
		$jsFileNames = array(
			"modules.Settings.$moduleName.resources.SwitchUsers",
		);
		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		$headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);
		return $headerScriptInstances;
	}
}
