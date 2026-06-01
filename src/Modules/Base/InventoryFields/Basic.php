<?php

namespace App\Modules\Base\InventoryFields;

use App\Runtime\CRM_Viewer;

/**
 * Inventory Basic Field Class
 * @package YetiForce.Fields
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 */
class Basic extends \App\Runtime\BaseModel
{

	protected $name = '';
	protected $defaultLabel = '';
	protected $defaultValue = '';
	protected $columnName = '-';
	protected $colSpan = 10;
	protected $dbType = 'string';
	protected $customColumn = [];
	protected $summationValue = false;
	protected $onlyOne = true;
	protected $displayTypeBase = ['LBL_DISPLAYTYPE_ALL' => 0, 'LBL_DISPLAYTYPE_ONLY_DETAIL' => 2, 'LBL_DISPLAYTYPE_HIDDEN' => 5, 'LBL_DISPLAYTYPE_READONLY' => 10];
	protected $blocks = [1];
	protected $fieldDataType = 'inventory';
	protected $params = [];

	/**
	 * Getting onlyOne field
	 * @return true/false
	 */
	public function isOnlyOne()
	{
		return $this->onlyOne;
	}

	public function getBlocks()
	{
		return $this->blocks;
	}

	/**
	 * Getting database-type of field
	 * @return string dbType
	 */
	public function getDBType()
	{
		return $this->dbType;
	}

	/**
	 * Getting all params values
	 * @return array
	 */
	public function getParams()
	{
		return $this->params;
	}

	public function getParamsConfig(): array
	{
		$params = $this->get('params');
		$config = [];
		if ($params !== null && $params !== '') {
			$decoded = \App\Utils\Json::decode($params);
			if (is_array($decoded)) {
				$config = $decoded;
			}
		}
		foreach ($this->getParams() as $param) {
			if (!array_key_exists($param, $config)) {
				$config[$param] = match ($param) {
					'modules' => [],
					'limit' => 0,
					'type' => '0',
					default => '',
				};
			}
		}
		return $config;
	}

	/**
	 * Getting all values display Type
	 * @return array
	 */
	public function displayTypeBase()
	{
		return $this->displayTypeBase;
	}

	public function getColSpan()
	{
		if ($this->has('colspan'))
			return $this->get('colspan');

		return $this->colSpan;
	}

	/**
	 * Getting template name
	 * @return string templateName
	 */
	public function getTemplateName($view, $moduleName)
	{
		$tpl = $view . $this->name . '.tpl';
		// First try module-specific template in active layout
		$filename = 'layouts' . DIRECTORY_SEPARATOR . \App\Runtime\Yeti_Layout::getActiveLayout() . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . $moduleName . DIRECTORY_SEPARATOR . 'inventoryfields' . DIRECTORY_SEPARATOR . $tpl;
		if (is_file($filename)) {
			return $tpl;
		}
		// Then try Base module in active layout (FreeCRM uses Base)
		$filename = 'layouts' . DIRECTORY_SEPARATOR . \App\Runtime\Yeti_Layout::getActiveLayout() . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . 'Base' . DIRECTORY_SEPARATOR . 'inventoryfields' . DIRECTORY_SEPARATOR . $tpl;
		if (is_file($filename)) {
			return $tpl;
		}
		// Fallback to default layout with module-specific template
		$filename = 'layouts' . DIRECTORY_SEPARATOR . CRM_Viewer::getDefaultLayoutName() . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . $moduleName . DIRECTORY_SEPARATOR . 'inventoryfields' . DIRECTORY_SEPARATOR . $tpl;
		if (is_file($filename)) {
			return $tpl;
		}
		// Fallback to default layout with Base module
		$filename = 'layouts' . DIRECTORY_SEPARATOR . CRM_Viewer::getDefaultLayoutName() . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . 'Base' . DIRECTORY_SEPARATOR . 'inventoryfields' . DIRECTORY_SEPARATOR . $tpl;
		if (is_file($filename)) {
			return $tpl;
		}
		// Last resort: return Base template
		return $view . 'Base' . '.tpl';
	}

	/**
	 * Getting default label
	 * @return string defaultLabel
	 */
	public function getDefaultLabel()
	{
		return $this->defaultLabel;
	}

	/**
	 * Getting field name
	 * @return string name
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * Getting column name
	 * @return string columnName
	 */
	public function getColumnName()
	{
		if ($this->has('columnname'))
			return $this->get('columnname');
		return $this->columnName;
	}

	/**
	 * Getting column name
	 * @return string customColumn
	 */
	public function getCustomColumn()
	{
		return $this->customColumn;
	}

	public function isSummary()
	{
		return $this->summationValue;
	}

	public function getDefaultValue()
	{
		if ($this->has('defaultvalue'))
			return $this->get('defaultvalue');
		return $this->defaultValue;
	}

	/**
	 * Data field instance initialization 
	 * @param array $valueArray Array for initialization
	 */
	public function initialize($valueArray)
	{
		$this->setData($valueArray);
	}

	public function getDisplayValue(mixed $value): string
	{
		return $value;
	}

	public function getEditValue(mixed $value): string
	{
		return $this->getDisplayValue($value);
	}

	public function getValue(mixed $value): string
	{
		if ($value == '') {
			return $this->get('defaultvalue');
		}
		return $value;
	}

	public function isMandatory()
	{
		return true;
	}

	/**
	 * Function to check whether the current field is visible
	 * @return boolean - true/false
	 */
	public function isVisible()
	{
		if ((int) $this->get('displaytype') == 5) {
			return false;
		}
		return true;
	}

	/**
	 * Function to check whether the current field is editable
	 * @return boolean - true/false
	 */
	public function isEditable()
	{
		if (!in_array((int) $this->get('displaytype'), [0, 10])) {
			return false;
		}
		return true;
	}

	/**
	 * Function to check whether the current field is editable
	 * @return boolean - true/false
	 */
	public function isColumnType()
	{
		if (empty($this->columnName) || $this->columnName == '-') {
			return false;
		}
		return true;
	}

	/**
	 * Getting value to display
	 * @return array
	 */
	public function modulesValues()
	{
		$modules = \App\Modules\Base\Models\Module::getAll([0], [], true);
		foreach ($modules AS $module) {
			$modulesNames[] = ['module' => $module->getName(), 'name' => $module->getName(), 'id' => $module->getName()];
		}
		return $modulesNames;
	}

	public function getSummaryValuesFromData($data)
	{
		$sum = 0;
		if (is_array($data)) {
			foreach ($data as $row) {
				$sum += $row[$this->get('columnname')];
			}
		}
		return $sum;
	}

	public function getMapDetail($returInstance = false)
	{
		$inventoryField = \App\Modules\Base\Models\InventoryField::getInstance($this->get('module'));
		$fields = $inventoryField->getAutoCompleteFields();
		$name = $this->getColumnName();
		if (isset($fields[$name])) {
			$mapDetail = $fields[$name];
			if ($returInstance) {
				$moduleModel = \App\Modules\Base\Models\Module::getInstance($mapDetail['module']);
				return \App\Modules\Base\Models\Field::getInstance($mapDetail['field'], $moduleModel);
			} else {
				return $mapDetail;
			}
		}
		return false;
	}

	public function getFieldDataType()
	{
		return $this->fieldDataType;
	}
}
