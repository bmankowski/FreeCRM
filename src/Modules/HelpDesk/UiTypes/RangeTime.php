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
		$isNull = is_null($value);
		if ($this->get('field')->getName() == 'response_time') {
			$value = round(\vtlib\Functions:: getDateTimeMinutesDiff($value, date('Y-m-d H:i:s')));
		}
		$result = \vtlib\Functions:: getRangeTime($value, !$isNull);
		$mode = $this->get('field')->getFieldParams();
		if (empty($mode)) {
			$mode = 'short';
		}
		return $result[$mode];
	}
}
