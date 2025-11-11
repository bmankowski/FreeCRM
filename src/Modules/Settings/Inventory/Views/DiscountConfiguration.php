<?php

namespace App\Modules\Settings\Inventory\Views;
use App\HttpVtiger_Request;



/**
 * @package YetiForce.Views
 * @license licenses/License.html
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 */
class DiscountConfiguration extends \App\Modules\Settings\Base\Views\Index
{

	public function getView()
	{
		return 'DiscountConfiguration';
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		
		\App\Log::trace('Start ' . __METHOD__);
		$qualifiedModule = $request->getModule(false);
		$view = $this->getView();
		$config = \App\Modules\Settings\Inventory\Models\Module::getConfig($view);
		$currentUser = $request->getUser();

		$viewer = $this->getViewer($request);
		$viewer->assign('PAGE_LABELS', $this->getPageLabels($request));
		$viewer->assign('VIEW', $view);
		$viewer->assign('QUALIFIED_MODULE', $qualifiedModule);
		$viewer->assign('USER_MODEL', $currentUser);
		$viewer->assign('CONFIG', $config);
		
		// Prepare Inventory Config-specific data for Config template
		$this->prepareInventoryConfigData($viewer, $view);
		
		// Check if this is an AJAX request - if so, return only content without MainLayout
		if ($request->isAjax()) {
			$viewer->view('Config.tpl', $qualifiedModule);
		} else {
			$viewer->view('ConfigIndex.tpl', $qualifiedModule);
		}
		\App\Log::trace('End ' . __METHOD__);
	}
	
	/**
	 * Prepare data for Inventory Config template
	 * Moves function calls from template to controller for better MVC separation
	 */
	protected function prepareInventoryConfigData($viewer, $view)
	{
		// Prepare picklist values
		$viewer->assign('AGGREGATION_PICKLIST_VALUES', \App\Modules\Settings\Inventory\Models\Module::getPicklistValues('aggregation'));
		
		// Determine field name based on view
		$field = ($view == 'DiscountConfiguration') ? 'discounts' : 'taxs';
		$viewer->assign('FIELD_PICKLIST_VALUES', \App\Modules\Settings\Inventory\Models\Module::getPicklistValues($field));
	}

	public function getPageLabels(\App\Http\Vtiger_Request $request)
	{
		$qualifiedModuleName = $request->getModule(false);
		$view = $this->getView();
		$translations = [];
		$translations['title'] = 'LBL_' . strtoupper($view);
		$translations['title_single'] = 'LBL_' . strtoupper($view) . '_SINGLE';
		$translations['description'] = 'LBL_' . strtoupper($view) . '_DESCRIPTION';
		return $translations;
	}

	public function getFooterScripts(\App\Http\Vtiger_Request $request)
	{
		$headerScriptInstances = parent::getFooterScripts($request);
		$moduleName = $request->getModule();

		$jsFileNames = ["modules.Settings.$moduleName.resources.Config"];

		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		$headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);
		return $headerScriptInstances;
	}
}
