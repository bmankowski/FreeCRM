<?php

namespace App\Modules\Settings\LayoutEditor\Actions;



/**
 * Save Inventory Action Class
 * @package YetiForce.Actions
 * @license licenses/License.html
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 */
class SaveAjax extends \App\Modules\Settings\Vtiger\Views\IndexAjax
{

	public function __construct()
	{
		parent::__construct();
		$this->exposeMethod('setInventory');
		$this->exposeMethod('saveInventoryField');
		$this->exposeMethod('saveSequence');
		$this->exposeMethod('delete');
	}

	public function setInventory(\App\Http\Vtiger_Request $request)
	{
		$param = $request->get('param');
		$moduleName = $param['module'];
		$status = false;
		$inventoryInstance = \Vtiger_Inventory_Model::getInstance($moduleName);
		$status = $inventoryInstance->setMode($param['status']);
		if ($status) {
			$status = true;
		}
		$response = new \App\Http\Vtiger_Response();
		$response->setResult([
			'success' => $status]
		);
		$response->emit();
	}

	/**
	 * Function is used to create and edit fields in advanced block
	 * @param \App\Http\Vtiger_Request $request
	 */
	public function saveInventoryField(\App\Http\Vtiger_Request $request)
	{
		$param = $request->get('param');
		$moduleName = $param['module'];
		$name = $param['name'];
		$id = $param['id'];
		$edit = false;
		$inventoryField = \\App\Modules\Vtiger\Models\InventoryField::getInstance($moduleName);
		if (!empty($id)) {
			$return = $inventoryField->saveField($name, $param);
			$edit = true;
		} else {
			$id = $inventoryField->addField($name, $param);
		}
		$arrayInstane = $inventoryField->getFields(false, [$id], 'Settings');
		$data = [];
		if (current($arrayInstane)) {
			$data = current($arrayInstane)->getData();
			$data['translate'] = \App\Runtime\Vtiger_Language_Handler::translate($data['label'], $moduleName);
		}
		$response = new \App\Http\Vtiger_Response();
		$response->setResult(['data' => $data, 'edit' => $edit]);
		$response->emit();
	}

	public function saveSequence(\App\Http\Vtiger_Request $request)
	{
		$param = $request->get('param');
		$moduleName = $param['module'];
		$inventoryField = \\App\Modules\Vtiger\Models\InventoryField::getInstance($moduleName);
		$status = $inventoryField->saveSequence($param['ids']);
		if ($status) {
			$status = true;
		}
		$response = new \App\Http\Vtiger_Response();
		$response->setResult(['success' => $status]);
		$response->emit();
	}

	public function delete(\App\Http\Vtiger_Request $request)
	{
		$param = $request->get('param');
		$moduleName = $param['module'];
		$inventoryField = \\App\Modules\Vtiger\Models\InventoryField::getInstance($moduleName);
		$status = $inventoryField->delete($param);
		if ($status) {
			$status = true;
		}
		$response = new \App\Http\Vtiger_Response();
		$response->setResult(['success' => $status]);
		$response->emit();
	}
}
