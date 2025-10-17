<?php

namespace App\Modules\Vtiger\UiTypes;

/**
 * UIType Modules Field Class
 * @package YetiForce.Fields
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */
class Modules extends Base
{

	/**
	 * Function to get the Template name for the current UI Type object
	 * @return string - Template Name
	 */
	public function getTemplateName()
	{
		return 'uitypes/Modules.tpl';
	}

	/**
	 * Function to get the Display Value, for the current field type with given DB Insert Value
	 * @param <Object> $value
	 * @return <Object>
	 */
	public function getDisplayValue($value, $record = false, $recordInstance = false, $rawText = false)
	{
		return \App\Runtime\Vtiger_Language_Handler::translate($value, $value);
	}

	public function getListSearchTemplateName()
	{
		return 'uitypes/ModulesFieldSearchView.tpl';
	}
}
