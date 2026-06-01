<?php

namespace App\Modules\Base\InventoryFields;

/**
 * Inventory Tax Field Class
 * @package YetiForce.Fields
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */
class Tax extends Basic
{

	protected $name = 'Tax';
	protected $defaultLabel = 'LBL_TAX';
	protected $defaultValue = 0;
	protected $columnName = 'tax';
	protected $dbType = 'decimal(27,8) DEFAULT 0';
	protected $customColumn = [
		'taxparam' => 'string'
	];
	protected $summationValue = true;

	public function getDisplayValue(mixed $value): string
	{
		return \App\Fields\CurrencyField::convertToUserFormat($value, null, true);
	}

	public function getClassName($data)
	{
		if (count($data) > 0 && $data[0]['taxmode'] == 0) {
			return 'hide';
		}
		return '';
	}
}
