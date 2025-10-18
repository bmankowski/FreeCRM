<?php

namespace App\Modules\Calendar\iCal;

class iCalendar_property_method extends iCalendar_property
{

	public $name = 'METHOD';
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
		// Methods from RFC 2446
		$methods = array('PUBLISH', 'REQUEST', 'REPLY', 'ADD', 'CANCEL', 'REFRESH', 'COUNTER', 'DECLINECOUNTER');
		return in_array($value, $methods);
	}
}