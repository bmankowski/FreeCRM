<?php

namespace App\Modules\Base\UiTypes;

/**
 * UIType total time field class
 * @package YetiForce.UIType
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */
class TotalTime extends BaseUiType
{

	public function getDisplayValue($value, $record = false, $recordInstance = false, $rawText = false)
	{
		$return = \vtlib\Functions::decimalTimeFormat($value);
		return $return['short'];
	}
}
