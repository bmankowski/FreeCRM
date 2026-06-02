<?php

namespace App\Modules\Settings\CustomView\Views;



/**
 * CustomView config view class
 * @package YetiForce.View
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 */
class Index extends \App\Modules\Settings\Base\Views\Index
{

	public function preProcess(\App\Http\Vtiger_Request $request, $display = true)
	{
		parent::preProcess($request, false);
		$viewer = $this->getViewer($request);
		$viewer->assign('SUPPORTED_MODULE_MODELS', \App\Modules\Settings\CustomView\Models\Module::getSupportedModules());
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$supportedModuleId = $request->get('sourceModule');
		$qualifiedModuleName = $request->getModule(false);
		$moduleModel = \App\Modules\Settings\LangManagement\Models\Module::getInstance($qualifiedModuleName);
		$viewer = $this->getViewer($request);
		$viewer->assign('SOURCE_MODULE_ID', $supportedModuleId);
		$viewer->assign('MODULE_MODEL', $moduleModel);
		$viewer->assign('QUALIFIED_MODULE', $qualifiedModuleName);
		$viewer->assign('MODULE', $moduleName);
		
		// Prepare CustomView-specific data for IndexContents template
		// Always assign OWNER_LABELS to prevent undefined array key warnings
		if ($supportedModuleId) {
			$this->prepareCustomViewIndexData($viewer, $supportedModuleId);
		} else {
			$viewer->assign('OWNER_LABELS', []);
		}
		
		if ($request->isAjax()) {
			// AJAX request - return content only
			$viewer->view('IndexContent.tpl', $qualifiedModuleName);
		} else {
			// Initial page load - return full page with MainLayout
			$viewer->view('Index.tpl', $qualifiedModuleName);
		}
	}
	
	/**
	 * Prepare data for CustomView IndexContents template
	 * Moves function calls from templates to controller for better MVC separation
	 */
	protected function prepareCustomViewIndexData($viewer, $sourceModuleId)
	{
		// Prepare owner labels for custom views
		$customViews = $viewer->getTemplateVars('MODULE_MODEL')->getCustomViews($sourceModuleId);
		$ownerLabels = [];
		foreach ($customViews as $key => $item) {
			if (isset($item['userid']) && $item['userid']) {
				$ownerLabels[$key] = \App\Fields\Owner::getLabel($item['userid']);
			} else {
				// Ensure all keys exist to prevent undefined array key warnings
				$ownerLabels[$key] = '';
			}
		}
		$viewer->assign('OWNER_LABELS', $ownerLabels);
	}

	/**
	 * Function to get the list of Script models to be included
	 * @param \App\Http\Vtiger_Request $request
	 * @return <Array> - List of ScriptAsset instances
	 */
	public function getFooterScripts(\App\Http\Vtiger_Request $request)
	{
		$headerScriptInstances = parent::getFooterScripts($request);

		return array_merge($headerScriptInstances, $this->checkAndConvertJsScripts([
			'modules.CustomView.resources.CustomView',
		]));
	}
}
