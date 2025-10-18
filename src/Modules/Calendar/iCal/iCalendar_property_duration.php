<?php

namespace App\Modules\Calendar\iCal;

class iCalendar_property_duration extends iCalendar_property
{

	public $name = 'DURATION';
	public $val_type = RFC2445_TYPE_DURATION;

	public function construct()
	{
		$this->valid_parameters = array(
			RFC2445_XNAME => RFC2445_OPTIONAL
		);
	}

	public function is_valid_value($value)
	{
		if (!parent::is_valid_value($value)) {
			return false;
		}

		// Value must be positive
		return ($value[0] != '-');
	}
}