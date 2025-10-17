<?php

namespace App\Modules\Settings\AutomaticAssignment\Views;



/**
 * Create View Class for Automatic assignment
 * @package YetiForce.Settings.ModalView
 * @license licenses/License.html
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 */

class Create extends \App\Modules\Settings\Vtiger\Views\BasicModal
{

	/**
	 * Function returns name that defines modal window size
	 * @param \App\Http\Vtiger_Request $request
	 * @return string
	 */
	public function getSize(\App\Http\Vtiger_Request $request)
	{
		return 'modal-sm';
	}

	/**
	 * Function proccess
	 * @param \App\Http\Vtiger_Request $request
	 */
	public function process(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule(false);
		$viewer = $this->getViewer($request);
		if ($request->has('tabid')) {
			$sourceModule = \App\Module::getModuleName($request->get('tabid'));
			$viewer->assign('SUPPORTED_FIELDS', \App\Modules\Settings\AutomaticAssignment\Models\Module::getFieldsByModule($sourceModule));
			$viewer->assign('SELECTED_MODULE', $sourceModule);
			$viewer->view('Create.tpl', $moduleName);
		} else {
			$viewer->assign('MODULE_MODEL', \App\Modules\Settings\Vtiger\Models\Module::getInstance($moduleName));
			$viewer->assign('WIZARD_BASE', true);
			$viewer->assign('SUPPORTED_MODULES', \App\Modules\Settings\AutomaticAssignment\Models\Module::getSupportedModules());
			$this->preProcess($request);
			$viewer->view('Create.tpl', $moduleName);
			$this->postProcess($request);
		}
	}
}
