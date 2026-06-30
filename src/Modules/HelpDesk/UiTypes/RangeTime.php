<?php

namespace App\Modules\HelpDesk\UiTypes;

use App\Modules\Base\UiTypes\BaseUiType;

/**
 * UIType RangeTime Field Class
 * @package YetiForce.Fields
 * @license licenses/License.html
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 */
class RangeTime extends BaseUiType
{

	public function getDisplayValue($value, $record = false, $recordInstance = false, $rawText = false)
	{
		$isEmpty = $value === null || $value === '';
		if ($this->get('field')->getName() === 'response_time') {
			if ($isEmpty) {
				$value = 0;
			} else {
				$value = (int) round(\vtlib\Functions::getDateTimeMinutesDiff($value, date('Y-m-d H:i:s')));
			}
		} elseif (!$isEmpty) {
			$value = (int) round((float) $value);
		} else {
			$value = 0;
		}
		$result = \vtlib\Functions::getRangeTime($value, !$isEmpty);
		$mode = $this->get('field')->getFieldParams();
		if (empty($mode)) {
			$mode = 'short';
		}
		return $result[$mode];
	}
}
