<?php

namespace App\Modules\Vtiger\UiTypes;

/**
 * UIType Record Number Field Class
 * @package YetiForce.Fields
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */
class RecordNumber extends BaseUiType
{

	/**
	 * Function to get the DB Insert Value, for the current field type with given User Value
	 * @param mixed $value
	 * @param \App\Modules\Vtiger\Models\Record $recordModel
	 * @return mixed
	 */
	public function getDBValue($value, $recordModel = false)
	{
		return \App\Fields\RecordNumber::incrementNumber(\App\Module::getModuleId($recordModel->getModuleName()));
	}
}
