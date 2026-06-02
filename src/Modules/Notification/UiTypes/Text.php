<?php

namespace App\Modules\Notification\UiTypes;

use App\Modules\Base\UiTypes\BaseUiType;

/**
 * Uitype Model
 * @package YetiForce.Github
 * @license licenses/License.html
 * @author Tomasz Kur <t.kur@yetiforce.com>
 */
class Text extends BaseUiType
{

	/**
	 * Function to get the Display Value, for the current field type with given DB Insert Value
	 * @param object $value
	 * @return object
	 */
	public function getDisplayValue($value, $record = false, $recordInstance = false, $rawText = false)
	{
		return nl2br($recordInstance->getParseField($this->get('field')->getName()));
	}
}
