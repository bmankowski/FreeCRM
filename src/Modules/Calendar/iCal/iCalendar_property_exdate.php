<?php

namespace App\Modules\Calendar\iCal;

class iCalendar_property_exdate extends iCalendar_property
{

	public $name = 'EXDATE';
	public $val_type = RFC2445_TYPE_DATE_TIME;
	public $val_multi = true;

	public function construct()
	{
		$this->valid_parameters = array(
			'TZID' => RFC2445_OPTIONAL | RFC2445_ONCE,
			'VALUE' => RFC2445_OPTIONAL | RFC2445_ONCE,
			RFC2445_XNAME => RFC2445_OPTIONAL
		);
	}

	public function is_valid_parameter($parameter, $value)
	{

		$parameter = strtoupper($parameter);

		if (!parent::is_valid_parameter($parameter, $value)) {
			return false;
		}
		if ($parameter == 'VALUE' && !($value == 'DATE' || $value == 'DATE-TIME')) {
			return false;
		}

		return true;
	}
}