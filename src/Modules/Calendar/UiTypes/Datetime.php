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

class Datetime extends \App\Modules\Base\UiTypes\Datetime
{

	public function getDisplayValue($value, $record = false, $recordInstance = false, $rawText = false)
	{
		if (empty($value)) {
			return '';
		}
		// date_start and due_date may be date-only or date+time depending on context
		if ($this->hasTimeComponent($value)) {
			return self::getDisplayDateTimeValue($value);
		}

		return \App\Modules\Base\UiTypes\Date::getDisplayDateValue($value);
	}

	public function hasTimeComponent($value)
	{
		$component = explode(' ', $value);
		return !empty($component[1]);
	}
}
