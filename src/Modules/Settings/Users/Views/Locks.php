<?php

namespace App\Modules\Settings\Users\Views;



/**
 * Locks View Class
 * @package YetiForce.View
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */

class Locks extends \App\Modules\Settings\Base\Views\Index
{

	public function getBreadcrumbTitle(\App\Http\Vtiger_Request $request)
	{
		return \App\Runtime\Vtiger_Language_Handler::translate('LBL_LOCKS', $request->getModule(false));
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$qualifiedModuleName = $request->getModule(false);
		$moduleModel = \App\Modules\Settings\Users\Models\Module::getInstance();
		$viewer = $this->getViewer($request);
		$viewer->assign('LOCKS', $moduleModel->getLocks());
		$viewer->assign('LOCKS_TYPE', $moduleModel->getLocksTypes());
		$viewer->assign('QUALIFIED_MODULE', $qualifiedModuleName);
		$viewer->assign('MODULE_MODEL', $moduleModel);
		$viewer->assign('MODULE', $moduleName);
		
		// Prepare Locks-specific data for LocksContent template
		$this->prepareLocksData($viewer);
		
		// Check if this is an AJAX request - if so, return only content without MainLayout
		if ($request->isAjax()) {
			$viewer->view('LocksContent.tpl', $qualifiedModuleName);
		} else {
			$viewer->view('Locks.tpl', $qualifiedModuleName);
		}
	}
	
	/**
	 * Prepare data for LocksContent template
	 * Moves function calls from template to controller for better MVC separation
	 */
	protected function prepareLocksData($viewer)
	{
		$viewer->assign('USERS', \App\Modules\Users\Models\Record::getAll());
		$viewer->assign('ROLES', \App\Modules\Settings\Roles\Models\Record::getAll());
	}

	public function getFooterScripts(\App\Http\Vtiger_Request $request)
	{
		$headerScriptInstances = parent::getFooterScripts($request);
		$moduleName = $request->getModule();
		$jsFileNames = array(
			"modules.Settings.$moduleName.resources.Locks",
		);
		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		$headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);
		return $headerScriptInstances;
	}
}
