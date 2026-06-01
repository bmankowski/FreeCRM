<?php

namespace App\Modules\Base\InventoryFields;

/**
 * Inventory Purchase Field Class
 * @package YetiForce.Fields
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */
class Purchase extends Basic
{

	protected $name = 'Purchase';
	protected $defaultLabel = 'LBL_PURCHASE';
	protected $defaultValue = 0;
	protected $columnName = 'purchase';
	protected $dbType = 'decimal(27,8) DEFAULT 0';
	protected $summationValue = true;

	public function getDisplayValue(mixed $value): string
	{
		return \App\Fields\CurrencyField::convertToUserFormat($value, null, true);
	}
}
