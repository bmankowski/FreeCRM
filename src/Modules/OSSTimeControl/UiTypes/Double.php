<?php

namespace App\Modules\OSSTimeControl\UiTypes;

use App\Modules\Base\UiTypes\BaseUiType;

/**
 * 
 * @package YetiForce.uitypes
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */
class Double extends BaseUiType
{

	/**
	 * Function to get the Display Value, for the current field type with given DB Insert Value
	 * @param string $value
	 * @param int $record id record
	 * @param \App\Modules\Base\Models\Record $recordInstance 
	 * @param mixed $rawText
	 * @return string
	 */
	public function getDisplayValue($value, $record = false, $recordInstance = false, $rawText = false)
	{
		if ($this->get('field')->getFieldName() === 'sum_time') {
			$return = \vtlib\Functions::decimalTimeFormat((double) $value);
			return $return['short'];
		} else {
			return parent::getDisplayValue($value, $record, $recordInstance, $rawText);
		}
	}
}
