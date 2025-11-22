<?php

namespace App\Modules\Notification\UiTypes;

use App\Modules\Base\UiTypes\BaseUiType;

/**
 * Uitype model 
 * @package YetiForce.UIType
 * @license licenses/License.html
 * @author Tomasz Kur <t.kur@yetiforce.com>
 */
class StringType extends BaseUiType
{

	/**
	 * If edit by ajax
	 * @return boolean
	 */
	public function isAjaxEditable()
	{
		return false;
	}

	/**
	 * Function to get the Display Value, for the current field type with given DB Insert Value
	 * @param string $value
	 * @param int $record id record
	 * @param <\App\Modules\Base\Models\Record> $recordInstance 
	 * @param mixed $rawText
	 * @return string
	 */
	public function getDisplayValue($value, $record = false, $recordInstance = false, $rawText = false)
	{
		return $recordInstance->getParseField($this->get('field')->getName());
	}
}
