<?php

namespace App\Modules\Base\InventoryFields;

/**
 * Inventory TotalPrice Field Class
 * @package YetiForce.Fields
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */
class TotalPrice extends Basic
{

	protected $name = 'TotalPrice';
	protected $defaultLabel = 'LBL_TOTAL_PRICE';
	protected $defaultValue = 0;
	protected $columnName = 'total';
	protected $dbType = 'decimal(27,8) DEFAULT 0';
	protected $summationValue = true;

	public function getDisplayValue(mixed $value): string
	{
		return \App\Fields\CurrencyField::convertToUserFormat($value, null, true);
	}
}
