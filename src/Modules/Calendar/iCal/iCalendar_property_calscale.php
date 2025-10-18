<?php

namespace App\Modules\Calendar\iCal;

class iCalendar_property_calscale extends iCalendar_property
{

	public $name = 'CALSCALE';
	public $val_type = RFC2445_TYPE_TEXT;

	public function construct()
	{
		$this->valid_parameters = array(
			RFC2445_XNAME => RFC2445_OPTIONAL
		);
	}

	public function is_valid_value($value)
	{
		// This is case-sensitive
		return ($value === 'GREGORIAN');
	}
}