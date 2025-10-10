<?php

namespace FreeCRM\Modules\Notification\UiTypes;

/**
 * Uitype Model
 * @package YetiForce.Github
 * @license licenses/License.html
 * @author Tomasz Kur <t.kur@yetiforce.com>
 */
class Text extends Base
{

	/**
	 * Function to get the Display Value, for the current field type with given DB Insert Value
	 * @param <Object> $value
	 * @return <Object>
	 */
	public function getDisplayValue($value, $record = false, $recordInstance = false, $rawText = false)
	{
		return nl2br($recordInstance->getParseField($this->get('field')->getName()));
	}
}
