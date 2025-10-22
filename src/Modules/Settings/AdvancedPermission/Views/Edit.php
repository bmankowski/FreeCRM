<?php

namespace App\Modules\Settings\AdvancedPermission\Views;



/**
 * Advanced permission edit view class
 * @package YetiForce.Settings.View
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */

class Edit extends \App\Modules\Settings\Vtiger\Views\Index
{

	public function __construct()
	{
		parent::__construct();
		$this->exposeMethod('step1');
		$this->exposeMethod('step2');
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$mode = $request->getMode();
		if (!empty($mode)) {
			echo $this->invokeExposedMethod($mode, $request);
		} else {
			$this->step1($request);
		}
	}

	/**
	 * Edit view first step
	 * @param \App\Http\Vtiger_Request $request
	 */
	public function step1(\App\Http\Vtiger_Request $request)
	{
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		$qualifiedModuleName = $request->getModule(false);
		$record = $request->get('record');

		if (!empty($record)) {
			$recordModel = \App\Modules\Settings\AdvancedPermission\Models\Record::getInstance($record);
		} else {
			$recordModel = new \App\Modules\Settings\AdvancedPermission\Models\Record();
		}
		$viewer->assign('RECORD_MODEL', $recordModel);
		$viewer->assign('RECORD_ID', $record);
		$viewer->assign('MODULE', $moduleName);
		$viewer->view('EditViewS1.tpl', $qualifiedModuleName);
	}

	/**
	 * Edit view second step
	 * @param \App\Http\Vtiger_Request $request
	 */
	public function step2(\App\Http\Vtiger_Request $request)
	{
		$qualifiedModuleName = $request->getModule(false);
		$record = $request->get('record');
		$recordModel = \App\Modules\Settings\AdvancedPermission\Models\Record::getInstance($record);
		$selectedModule = \App\Module::getModuleName($recordModel->get('tabid'));
		$moduleModel = \App\Modules\Vtiger\Models\Module::getInstance($selectedModule);
		$recordStructureInstance = \App\Modules\Vtiger\Models\RecordStructure::getInstanceForModule($moduleModel);

		$viewer = $this->getViewer($request);
		$viewer->assign('RECORD_STRUCTURE', $recordStructureInstance->getStructure());
		$viewer->assign('ADVANCE_CRITERIA', \\App\Modules\Vtiger\AdvancedFilter::transformToAdvancedFilterCondition($recordModel->get('conditions')));
		$viewer->assign('SOURCE_MODULE', $selectedModule);
		$viewer->assign('RECORD_MODEL', $recordModel);
		$viewer->assign('RECORD_ID', $record);
		$viewer->assign('MODULE', 'Settings:Workflows');
		$viewer->view('EditViewS2.tpl', $qualifiedModuleName);
	}

	public function getFooterScripts(\App\Http\Vtiger_Request $request)
	{
		$headerScriptInstances = parent::getFooterScripts($request);
		$jsFileNames = [
			'modules.Vtiger.resources.AdvanceFilterEx',
			'modules.Settings.AdvancedPermission.resources.Edit',
		];
		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		return array_merge($headerScriptInstances, $jsScriptInstances);
	}
}
