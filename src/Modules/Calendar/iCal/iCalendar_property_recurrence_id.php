<?php

namespace App\Modules\Calendar\iCal;

class iCalendar_property_recurrence_id extends iCalendar_property
{

	public $name = 'RECURRENCE-ID';
	public $val_type = RFC2445_TYPE_DATE_TIME;

	public function construct()
	{
		$this->valid_parameters = array(
			'RANGE' => RFC2445_OPTIONAL | RFC2445_ONCE,
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