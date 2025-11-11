<?php

namespace App\Modules\Settings\RecordAllocation\Views;



/**
 * Record allocation
 * @package YetiForce.View
 * @license licenses/License.html
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 */
class Index extends \App\Modules\Settings\Base\Views\Index
{

	public function __construct()
	{
		parent::__construct();
		$this->exposeMethod('getPanel');
	}

	public function preProcess(\App\Http\Vtiger_Request $request, $display = true)
	{
		parent::preProcess($request, false);
		$moduleName = $request->getModule();
		$qualifiedModuleName = $request->getModule(false);
		$viewer = $this->getViewer($request);
		$viewer->assign('QUALIFIED_MODULE', $qualifiedModuleName);
		$viewer->assign('MODULE', $moduleName);
		// MainLayout handles rendering, no separate preProcess template needed
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$mode = $request->getMode();
		if (!empty($mode)) {
			echo $this->invokeExposedMethod($mode, $request);
			return;
		}
		$moduleName = $request->getModule();
		$qualifiedModuleName = $request->getModule(false);
		$type = $request->get('type');
		if (empty($type)) {
			$type = 'owner';
		}
		$viewer = $this->getViewer($request);
		$viewer->assign('TYPE', $type);
		$viewer->assign('QUALIFIED_MODULE', $qualifiedModuleName);
		$viewer->assign('MODULE', $moduleName);
		
		// Prepare RecordAllocation-specific data for IndexContent template
		$this->prepareRecordAllocationData($viewer, $type);

		if ($request->isAjax()) {
			$viewer->view('IndexContent.tpl', $qualifiedModuleName);
		} else {
			$viewer->view('IndexView.tpl', $qualifiedModuleName);
		}
	}
	
	/**
	 * Prepare data for RecordAllocation IndexContent template
	 * Moves function calls from templates to controller for better MVC separation
	 */
	protected function prepareRecordAllocationData($viewer, $type)
	{
		$viewer->assign('ALL_ACTIVEUSER_LIST', \App\Fields\Owner::getInstance()->getAccessibleUsers('Public'));
		$allModuleList = \App\Modules\Base\Models\Module::getAll([0], [], true);
		$viewer->assign('ALL_MODULE_LIST', $allModuleList);
		
		// Prepare record allocation data per module
		$moduleAllocationData = [];
		$moduleGroupLists = [];
		$moduleDataJson = [];
		foreach ($allModuleList as $moduleId => $moduleModel) {
			$moduleName = $moduleModel->getName();
			$data = \App\Modules\Settings\RecordAllocation\Models\Module::getRecordAllocationByModule($type, $moduleName);
			if ($data) {
				$moduleAllocationData[$moduleName] = $data;
				$moduleDataJson[$moduleName] = \App\Modules\Base\Helpers\Util::toSafeHTML(\App\Json::encode($data));
			}
			// Prepare group lists per module for AddPanel
			$moduleGroupLists[$moduleName] = \App\Fields\Owner::getInstance($moduleName)->getAccessibleGroups('Public');
		}
		$viewer->assign('MODULE_ALLOCATION_DATA', $moduleAllocationData);
		$viewer->assign('MODULE_GROUP_LISTS', $moduleGroupLists);
		$viewer->assign('MODULE_DATA_JSON', $moduleDataJson);
	}

	public function getPanel(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$qualifiedModuleName = $request->getModule(false);
		$index = (int) $request->get('index');
		$type = $request->get('type');
		if (empty($type)) {
			$type = 'owner';
		}
		$sourceModule = $request->get('sourceModule');
		$viewer = $this->getViewer($request);
		$viewer->assign('TYPE', $type);
		$viewer->assign('QUALIFIED_MODULE', $qualifiedModuleName);
		$viewer->assign('MODULE_NAME', $sourceModule);
		$viewer->assign('MODULE_ID', \vtlib\Functions::getModuleId($sourceModule));
		$viewer->assign('INDEX', ++$index);
		$viewer->assign('DATA', \App\Modules\Settings\RecordAllocation\Models\Module::getRecordAllocationByModule($type, $sourceModule));
		$viewer->assign('MODULE', $moduleName);
		
		// Prepare AddPanel-specific data
		$this->prepareAddPanelData($viewer, $sourceModule);
		
		$viewer->view('AddPanel.tpl', $qualifiedModuleName);
	}
	
	/**
	 * Prepare data for AddPanel template
	 * Moves function calls from templates to controller for better MVC separation
	 */
	protected function prepareAddPanelData($viewer, $moduleName)
	{
		$viewer->assign('ALL_ACTIVEUSER_LIST', \App\Fields\Owner::getInstance()->getAccessibleUsers('Public'));
		$viewer->assign('ALL_ACTIVEGROUP_LIST', \App\Fields\Owner::getInstance($moduleName)->getAccessibleGroups('Public'));
		
		// Prepare JSON-encoded data
		$data = $viewer->getTemplateVars('DATA');
		$viewer->assign('DATA_JSON', \App\Modules\Base\Helpers\Util::toSafeHTML(\App\Json::encode($data ? $data : [])));
	}

	public function getFooterScripts(\App\Http\Vtiger_Request $request)
	{
		$headerScriptInstances = parent::getFooterScripts($request);
		$moduleName = $request->getModule();

		$jsFileNames = [
			'~libraries/jquery/datatables/media/js/jquery.dataTables.min.js',
			'~libraries/jquery/datatables/plugins/integration/bootstrap/3/dataTables.bootstrap.min.js'
		];
		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		$headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);
		return $headerScriptInstances;
	}

	public function getHeaderCss(\App\Http\Vtiger_Request $request)
	{
		$headerCssInstances = parent::getHeaderCss($request);
		$cssFileNames = [
			'~libraries/jquery/datatables/media/css/jquery.dataTables_themeroller.css',
			'~libraries/jquery/datatables/plugins/integration/bootstrap/3/dataTables.bootstrap.css',
		];
		$cssInstances = $this->checkAndConvertCssStyles($cssFileNames);
		$headerCssInstances = array_merge($headerCssInstances, $cssInstances);

		return $headerCssInstances;
	}
}
