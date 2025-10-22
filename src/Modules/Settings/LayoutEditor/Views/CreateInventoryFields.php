<?php

namespace App\Modules\Settings\LayoutEditor\Views;



/**
 * Inventory Field View Class
 * @package YetiForce.Views
 * @license licenses/License.html
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 */
class CreateInventoryFields extends \App\Modules\Settings\Vtiger\Views\IndexAjax
{

	public function __construct()
	{
		parent::__construct();
		$this->exposeMethod('step1');
		$this->exposeMethod('step2');
	}

	public function step1(\App\Http\Vtiger_Request $request)
	{
		$qualifiedModuleName = $request->getModule(false);
		$moduleName = $request->get('type');
		$block = $request->get('block');
		$instance = \\App\Modules\Vtiger\Models\InventoryField::getInstance($moduleName);
		$models = $instance->getAllFields();

		$fieldsName = [];
		foreach ($instance->getFields(1, [], 'Settings') AS $fields) {
			$fieldsName = array_merge(array_keys($fields), $fieldsName);
		}
		$viewer = $this->getViewer($request);
		$viewer->assign('FIELDSEXISTS', $fieldsName);
		$viewer->assign('MODULE_MODELS', $models);
		$viewer->assign('BLOCK', $block);
		$viewer->assign('MODULE', $qualifiedModuleName);
		$viewer->assign('QUALIFIED_MODULE', $qualifiedModuleName);
		$viewer->view('CreateInventoryFieldsStep1.tpl', $qualifiedModuleName);
	}

	public function step2(\App\Http\Vtiger_Request $request)
	{
		$qualifiedModuleName = $request->getModule(false);
		$type = $request->get('mtype');
		$moduleName = $request->get('type');
		$id = $request->get('id');
		$instance = \\App\Modules\Vtiger\Models\InventoryField::getInstance($moduleName);
		if ($id) {
			$fieldInstance = $instance->getFields(false, [$id], 'Settings');
		} else {
			$models = $instance->getAllFields();
			$fieldInstance = $models[$type];
		}
		$viewer = $this->getViewer($request);
		$viewer->assign('INVENTORY_MODEL', $instance);
		$viewer->assign('FIELD_INSTANCE', $fieldInstance);
		$viewer->assign('QUALIFIED_MODULE', $qualifiedModuleName);
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('ID', $request->get('id'));
		$viewer->view('CreateInventoryFieldsStep2.tpl', $qualifiedModuleName);
	}
}
