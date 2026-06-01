<?php

namespace App\Modules\Base\InventoryFields;

/**
 * Inventory Margin Field Class
 * @package YetiForce.Fields
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 */
class Margin extends Basic
{

	protected $name = 'Margin';
	protected $defaultLabel = 'LBL_MARGIN';
	protected $defaultValue = 0;
	protected $columnName = 'margin';
	protected $dbType = 'decimal(27,8) DEFAULT 0';
	protected $summationValue = true;

	public function getDisplayValue(mixed $value): string
	{
		return \App\Fields\CurrencyField::convertToUserFormat($value, null, true);
	}
}
