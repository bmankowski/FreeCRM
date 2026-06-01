<?php

namespace App\Modules\Calendar\UiTypes;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

class Time extends \App\Modules\Base\UiTypes\Time
{

	public function getEditViewDisplayValue($value, $record = false)
	{
		if (!empty($value)) {
			return parent::getEditViewDisplayValue($value);
		}

		$specialTimeFields = array('time_start', 'time_end');

		$fieldInstance = $this->get('field')->getWebserviceFieldObject();
		$fieldName = $fieldInstance->getFieldName();

		if (!in_array($fieldName, $specialTimeFields)) {
			return parent::getEditViewDisplayValue($value);
		} else {
			return $this->getDisplayTimeDifferenceValue($fieldName, $value);
		}
	}

	public function getDisplayTimeDifferenceValue(string $fieldName, ?string $value): string
	{
		if ($fieldName === 'time_end' && ($value === '' || $value === null)) {
			return '';
		}

		$userModel = \App\Modules\Users\Models\Privileges::getCurrentUserModel();
		$date = new \DateTime($value);

		$dateTimeField = new \App\Fields\DateTimeField($date->format('Y-m-d H:i:s'));
		$value = $dateTimeField->getDisplayTime();
		return $value;
	}
}
