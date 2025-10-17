<?php

namespace App\Modules\Notification\UiTypes;

/**
 * Uitype model 
 * @package YetiForce.UIType
 * @license licenses/License.html
 * @author Tomasz Kur <t.kur@yetiforce.com>
 */
class String extends Base
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
	 * @param <\App\Modules\Vtiger\Models\Record> $recordInstance 
	 * @param mixed $rawText
	 * @return string
	 */
	public function getDisplayValue($value, $record = false, $recordInstance = false, $rawText = false)
	{
		return $recordInstance->getParseField($this->get('field')->getName());
	}
}
