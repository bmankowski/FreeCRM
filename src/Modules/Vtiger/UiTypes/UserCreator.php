<?php

namespace App\Modules\Vtiger\UiTypes;

/**
 * UIType User Field Class
 * @package YetiForce.Fields
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */
class UserCreator extends Base
{

	/**
	 * Function to get the template name for the current UI Type object
	 * @return string - Template Name
	 */
	public function getListSearchTemplateName()
	{
		return 'uitypes/OwnerFieldSearchView.tpl';
	}
	
	/**
	 * Function to get the DB Insert Value, for the current field type with given User Value
	 * @param mixed $value
	 * @param \App\Modules\Vtiger\Models\Record $recordModel
	 * @return mixed
	 */
	public function getDBValue($value, $recordModel = false)
	{
		return \App\Modules\Users\Models\Record::getCurrentUserId();
	}
}
