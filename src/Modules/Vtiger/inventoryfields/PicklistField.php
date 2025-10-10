<?php

namespace FreeCRM\Modules\Vtiger;

/**
 * Inventory Picklist from Field Class
 * @package YetiForce.Fields
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */
class PicklistField extends InventoryField
{

	protected $name = 'PicklistField';
	protected $defaultLabel = 'LBL_PICKLIST_FIELD';
	protected $columnName = 'picklistfield';
	protected $dbType = 'string';
	protected $onlyOne = false;

	public function getParams()
	{
		$inventoryFieldModel = Vtiger_InventoryField_Model::getInstance($this->get('module'));
		$fields = $inventoryFieldModel->getFields(true);
		$mainParams = $inventoryFieldModel->getMainParams($fields[1]);
		return $mainParams['modules'];
	}

	public function getPicklist($moduleName)
	{
		$values = [];
		$moduleModel = \FreeCRM\Modules\Vtiger\Models\Module::getInstance($moduleName);
		foreach ($moduleModel->getFieldsByType(['picklist']) as $fieldName => $fieldModel) {
			$values[$fieldName] = vtranslate($fieldModel->get('label'), $moduleName);
		}
		return $values;
	}

	public function getPicklistValues($rowId)
	{
		$modules = $this->getParamsConfig();
		if (!empty($rowId)) {
			$moduleName = \vtlib\Functions::getCRMRecordType($rowId);
			foreach ($modules as $module => $field) {
				if ($module != $moduleName) {
					unset($modules[$module]);
				}
			}
		}
		$values = [];
		foreach ($modules as $module => $field) {
			foreach (\App\Fields\Picklist::getPickListValues($field) as $value) {
				$values[] = [
					'module' => $module,
					'value' => $value,
					'name' => vtranslate($value, $module)
				];
			}
		}
		return $values;
	}
}
