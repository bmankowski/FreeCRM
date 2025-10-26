<?php

namespace App\Modules\Base\InventoryFields;

/**
 * Inventory MarginP Field Class
 * @package YetiForce.Fields
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 */
class MarginP extends Basic
{

	protected $name = 'MarginP';
	protected $defaultLabel = 'LBL_MARGIN_PERCENT';
	protected $defaultValue = 0;
	protected $columnName = 'marginp';
	protected $dbType = 'decimal(27,8) DEFAULT 0';
	protected $summationValue = true;
	protected $colSpan = 15;

	/**
	 * Getting value to display
	 * @param type $value
	 * @return type
	 */
	public function getDisplayValue($value)
	{
		return CurrencyField::convertToUserFormat($value, null, true);
	}

	public function getSummaryValuesFromData($data)
	{
		$sum = 0;
		if (is_array($data)) {
			foreach ($data as $row) {
				$purchase += $row['purchase'];
				$margin += $row['margin'];
			}
			if (!empty($purchase)) {
				$sum = ($margin / $purchase) * 100;
			}
		}
		return $sum;
	}
}
