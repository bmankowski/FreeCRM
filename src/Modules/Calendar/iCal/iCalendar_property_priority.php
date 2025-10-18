<?php

namespace App\Modules\Calendar\iCal;

class iCalendar_property_priority extends iCalendar_property
{

	public $name = 'PRIORITY';
	public $val_type = RFC2445_TYPE_TEXT;

	public function construct()
	{
		$this->valid_parameters = array(
			RFC2445_XNAME => RFC2445_OPTIONAL
		);
	}

	public function is_valid_value($value)
	{
		// Only integers between 0 and 9 inclusive allowed        
		if (!parent::is_valid_value($value)) {
			return false;
		}
		return true;
	}
}