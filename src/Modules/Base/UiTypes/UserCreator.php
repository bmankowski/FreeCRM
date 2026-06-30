<?php

namespace App\Modules\Base\UiTypes;

/**
 * UIType User Field Class
 * @package YetiForce.Fields
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */
class UserCreator extends BaseUiType
{

	/**
	 * Function to get the template name for the current UI Type object
	 * @return string - Template Name
	 */
	public function getListSearchTemplateName()
	{
		return 'uitypes/OwnerFieldSearchView.tpl';
	}

	public function getDisplayValue($value, $record = false, $recordInstance = false, $rawText = false)
	{
		if (empty($value)) {
			return '';
		}
		return \App\Fields\Owner::getLabel($value);
	}

	public function getListViewDisplayValue($value, $record = false, $recordInstance = false, $rawText = false)
	{
		if (empty($value)) {
			return '';
		}
		return \vtlib\Functions::textLength(\App\Fields\Owner::getLabel($value), $this->get('field')->get('maxlengthtext'));
	}

	/**
	 * Function to get the DB Insert Value, for the current field type with given User Value
	 * @param mixed $value
	 * @param \App\Modules\Base\Models\Record $recordModel
	 * @return mixed
	 */
	public function getDBValue($value, $recordModel = false)
	{
		return (int) (\App\User\CurrentUser::getId() ?? 0);
	}
}
