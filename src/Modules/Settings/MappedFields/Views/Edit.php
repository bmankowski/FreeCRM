<?php

namespace App\Modules\Settings\MappedFields\Views;



/**
 * Edit View Class for MappedFields Settings
 * @package YetiForce.View
 * @license licenses/License.html
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 */

class Edit extends \App\Modules\Settings\Base\Views\Index
{

	public function process(\App\Http\Vtiger_Request $request)
	{
		$step = strtolower($request->getMode());
		$this->step($step, $request);
	}

	public function preProcess(\App\Http\Vtiger_Request $request, $display = true)
	{
		parent::preProcess($request);
		$viewer = $this->getViewer($request);

		$recordId = $request->get('record');
		$viewer->assign('RECORDID', $recordId);
		if ($recordId) {
			$moduleInstance = \App\Modules\Settings\MappedFields\Models\Module::getInstanceById($recordId);
			$viewer->assign('MAPPEDFIELDS_MODULE_MODEL', $moduleInstance);
		}
		$viewer->assign('RECORD_MODE', $request->getMode());
		$viewer->view('EditHeader.tpl', $request->getModule(false));
	}

	public function step($step, \App\Http\Vtiger_Request $request)
	{
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		$qualifiedModuleName = $request->getModule(false);

		$recordId = $request->get('record');
		if ($recordId) {
			$moduleInstance = \App\Modules\Settings\MappedFields\Models\Module::getInstanceById($recordId);
			$viewer->assign('RECORDID', $recordId);
			$viewer->assign('MODE', 'edit');
		} else {
			$moduleInstance = \App\Modules\Settings\MappedFields\Models\Module::getCleanInstance();
		}
		$viewer->assign('MAPPEDFIELDS_MODULE_MODEL', $moduleInstance);
		$allModules = \App\Modules\Settings\MappedFields\Models\Module::getSupportedModules();
		$viewer->assign('ALL_MODULES', $allModules);
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('QUALIFIED_MODULE', $qualifiedModuleName);

		switch ($step) {
			case 'step4':
				// Prepare MappedFields Step4-specific data for Step4 template
				$this->prepareMappedFieldsStep4Data($viewer);
				$viewer->view('Step4.tpl', $qualifiedModuleName);
				break;
			case 'step3':
				$moduleSourceName = \App\Utils\ModuleUtils::getModuleName($moduleInstance->get('tabid'));
				$moduleModel = \App\Modules\Base\Models\Module::getInstance($moduleSourceName);
				$recordStructureInstance = \App\Modules\Base\Models\RecordStructure::getInstanceForModule($moduleModel);
				$viewer->assign('RECORD_STRUCTURE', $recordStructureInstance->getStructure());
				$viewer->assign('SOURCE_MODULE', $moduleSourceName);
				$viewer->assign('ADVANCE_CRITERIA', \App\Modules\Base\Helpers\AdvancedFilter::transformToAdvancedFilterCondition($moduleInstance->get('conditions')));
				$viewer->view('Step3.tpl', $qualifiedModuleName);
				break;
			case 'step2':
				$assignedToValues = [];
				$assignedToValues['LBL_USERS'] = \App\Fields\Owner::getInstance()->getAccessibleUsers();
				$assignedToValues['LBL_GROUPS'] = \App\Fields\Owner::getInstance()->getAccessibleGroups();
				$viewer->assign('SEL_MODULE_MODEL', \App\Modules\Settings\MappedFields\Models\Module::getInstance($moduleInstance->get('tabid')));
				$viewer->assign('REL_MODULE_MODEL', \App\Modules\Settings\MappedFields\Models\Module::getInstance($moduleInstance->get('reltabid')));
				$viewer->assign('USERS_LIST', $assignedToValues);
				$viewer->view('Step2.tpl', $qualifiedModuleName);
				break;
			case 'step1':
			default:
				$viewer->view('Step1.tpl', $qualifiedModuleName);
				break;
		}
	}

	public function getFooterScripts(\App\Http\Vtiger_Request $request)
	{
		$headerScriptInstances = parent::getFooterScripts($request);
		$moduleName = $request->getModule();

		$jsFileNames = [
			'modules.Settings.Vtiger.resources.Edit',
			"modules.Settings.$moduleName.resources.Edit",
			"modules.Settings.$moduleName.resources.Edit1",
			"modules.Settings.$moduleName.resources.Edit2",
			"modules.Settings.$moduleName.resources.Edit3",
			"modules.Settings.$moduleName.resources.Edit4",
			'modules.Base.resources.AdvanceFilter',
			'modules.Base.resources.AdvanceFilterEx',
		];

		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		$headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);
		return $headerScriptInstances;
	}
	
	/**
	 * Prepare data for MappedFields Step4 template
	 * Moves function calls from template to controller for better MVC separation
	 */
	protected function prepareMappedFieldsStep4Data($viewer)
	{
		$viewer->assign('GROUP_MEMBERS', \App\Modules\Settings\Groups\Models\Member::getAll(false));
	}
}
