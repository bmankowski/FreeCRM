<?php

namespace App\Modules\Base\UiTypes;

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
	 * @param \App\Modules\Base\Models\Record $recordModel
	 * @return mixed
	 */
	public function getDBValue($value, $recordModel = false)
	{
		return \App\Fields\RecordNumber::incrementNumber(\App\Utils\ModuleUtils::getModuleId($recordModel->getModuleName()));
	}
}
