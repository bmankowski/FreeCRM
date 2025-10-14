<?php

namespace FreeCRM\Modules\Settings\AutomaticAssignment\Views;



/**
 * Create View Class for Automatic assignment
 * @package YetiForce.Settings.ModalView
 * @license licenses/License.html
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 */

use FreeCRM\Modules\Settings\AutomaticAssignment\Models\Module as Settings_AutomaticAssignment_Module_Model;
class Create extends \FreeCRM\Modules\Settings\Vtiger\Views\BasicModal
{

	/**
	 * Function returns name that defines modal window size
	 * @param \FreeCRM\Http\Vtiger_Request $request
	 * @return string
	 */
	public function getSize(\FreeCRM\Http\Vtiger_Request $request)
	{
		return 'modal-sm';
	}

	/**
	 * Function proccess
	 * @param \FreeCRM\Http\Vtiger_Request $request
	 */
	public function process(\FreeCRM\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule(false);
		$viewer = $this->getViewer($request);
		if ($request->has('tabid')) {
			$sourceModule = \App\Module::getModuleName($request->get('tabid'));
			$viewer->assign('SUPPORTED_FIELDS', Settings_AutomaticAssignment_Module_Model::getFieldsByModule($sourceModule));
			$viewer->assign('SELECTED_MODULE', $sourceModule);
			$viewer->view('Create.tpl', $moduleName);
		} else {
			$viewer->assign('MODULE_MODEL', Settings_Vtiger_Module_Model::getInstance($moduleName));
			$viewer->assign('WIZARD_BASE', true);
			$viewer->assign('SUPPORTED_MODULES', Settings_AutomaticAssignment_Module_Model::getSupportedModules());
			$this->preProcess($request);
			$viewer->view('Create.tpl', $moduleName);
			$this->postProcess($request);
		}
	}
}
