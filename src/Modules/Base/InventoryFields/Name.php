<?php

namespace App\Modules\Base\InventoryFields;

/**
 * Inventory Name Field Class
 * @package YetiForce.Fields
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */
class Name extends Basic
{

	protected $name = 'Name';
	protected $defaultLabel = 'LBL_ITEM_NAME';
	protected $columnName = 'name';
	protected $dbType = 'int DEFAULT 0';
	protected $params = ['modules', 'limit'];
	protected $colSpan = 30;

	/**
	 * Getting value to display
	 * @param type $value
	 * @return type
	 */
	public function getDisplayValue($value)
	{
		if ($value != 0)
			return \App\Record::getLabel($value);
		return '';
	}

	/**
	 * Getting value to display
	 * @return array
	 */
	public function limitValues()
	{
		return [
				['id' => 0, 'name' => 'LBL_NO'],
				['id' => 1, 'name' => 'LBL_YES']
		];
	}

	public function getConfig()
	{
		return \App\Utils\Json::decode($this->get('params'));
	}
}
