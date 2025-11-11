<?php

namespace App\Modules\Settings\CustomView\Views;



/**
 * FilterPermissions View Class for CustomView
 * @package YetiForce.ModalView
 * @license licenses/License.html
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 */
class FilterPermissions extends \App\Modules\Settings\Base\Views\BasicModal
{

	public function process(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule(false);
		$sourceModuleId = $request->get('sourceModule');
		$moduleModel = \App\Modules\Settings\LangManagement\Models\Module::getInstance($moduleName);

		$viewer = $this->getViewer($request);
		$viewer->assign('IS_DEFAULT', $request->get('isDefault'));
		$viewer->assign('TYPE', $request->get('type'));
		$viewer->assign('MODULE_NAME', $moduleName);
		$viewer->assign('SOURCE_MODULE', $sourceModuleId);
		$viewer->assign('CVID', $request->get('cvid'));
		$viewer->assign('MODULE_MODEL', $moduleModel);
		$this->preProcess($request);
		
		// Prepare CustomView FilterPermissions-specific data for FilterPermissions template
		$this->prepareCustomViewFilterPermissionsData($viewer);
		
		$viewer->view('FilterPermissions.tpl', $moduleName);
		$this->postProcess($request);
	}
	
	/**
	 * Prepare data for CustomView FilterPermissions template
	 * Moves function calls from template to controller for better MVC separation
	 */
	protected function prepareCustomViewFilterPermissionsData($viewer)
	{
		$viewer->assign('MEMBERS', \App\Modules\Settings\Groups\Models\Member::getAll());
	}
}
