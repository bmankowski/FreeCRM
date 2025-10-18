<?php

namespace App\Modules\Calendar\iCal;

class iCalendar_property_dtstamp extends iCalendar_property
{

	public $name = 'DTSTAMP';
	public $val_type = RFC2445_TYPE_DATE_TIME;

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
		// Time MUST be in UTC format
		return(substr($value, -1) == 'Z');
	}
}