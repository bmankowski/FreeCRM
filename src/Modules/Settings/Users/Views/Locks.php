<?php

namespace FreeCRM\Modules\Settings\Users\Views;



/**
 * Locks View Class
 * @package YetiForce.View
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */

use FreeCRM\Modules\Users\Models\Module as Users_Module_Model;
class Locks extends \FreeCRM\Modules\Settings\Vtiger\Views\Index
{

	public function getBreadcrumbTitle(\FreeCRM\Http\Vtiger_Request $request)
	{
		return \FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_LOCKS', $request->getModule(false));
	}

	public function process(\FreeCRM\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$qualifiedModuleName = $request->getModule(false);
		$moduleModel = Settings_Users_Module_Model::getInstance();
		$viewer = $this->getViewer($request);
		$viewer->assign('LOCKS', $moduleModel->getLocks());
		$viewer->assign('LOCKS_TYPE', $moduleModel->getLocksTypes());
		$viewer->assign('QUALIFIED_MODULE', $qualifiedModuleName);
		$viewer->assign('MODULE_MODEL', $moduleModel);
		$viewer->assign('MODULE', $moduleName);
		$viewer->view('Locks.tpl', $qualifiedModuleName);
	}

	public function getFooterScripts(\FreeCRM\Http\Vtiger_Request $request)
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
