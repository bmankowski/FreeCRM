<?php

namespace App\Modules\Settings\AdvancedPermission\Views;



/**
 * Modal view
 * @package YetiForce.View
 * @license licenses/License.html
 * @author Tomasz Kur <t.kur@yetiforce.com>
 */
class RecalculatePermission extends \App\Modules\Settings\Base\Views\BasicModal
{

	public function process(\App\Http\Vtiger_Request $request)
	{
		parent::preProcess($request);
		$qualifiedModuleName = $request->getModule(false);
		$modules = \vtlib\Functions:: getAllModules();
		$viewer = $this->getViewer($request);
		$viewer->assign('MODULE_NAME', $request->getModule(true));
		$viewer->assign('MODULE', $qualifiedModuleName);
		$viewer->assign('LIST_MODULES' , $modules);
		$viewer->view('RecalculatePermission.tpl', $qualifiedModuleName);
		parent::postProcess($request);
	}
}
