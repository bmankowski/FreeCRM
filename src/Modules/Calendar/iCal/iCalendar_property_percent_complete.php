<?php

namespace App\Modules\Calendar\iCal;

class iCalendar_property_percent_complete extends iCalendar_property
{

	public $name = 'PERCENT-COMPLETE';
	public $val_type = RFC2445_TYPE_INTEGER;

	public function construct()
	{
		$this->valid_parameters = array(
			RFC2445_XNAME => RFC2445_OPTIONAL
		);
	}

	public function is_valid_value($value)
	{
		// Only integers between 0 and 100 inclusive allowed
		if (!parent::is_valid_value($value)) {
			return false;
		}
		$value = intval($value);
		return ($value >= 0 && $value <= 100);
	}
}