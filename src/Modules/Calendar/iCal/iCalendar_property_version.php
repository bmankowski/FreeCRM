<?php

namespace App\Modules\Calendar\iCal;

class iCalendar_property_version extends iCalendar_property
{

	public $name = 'VERSION';
	public $val_type = RFC2445_TYPE_TEXT;
	public $val_default = '2.0';

	public function construct()
	{
		$this->valid_parameters = array(
			RFC2445_XNAME => RFC2445_OPTIONAL
		);
	}

	public function is_valid_value($value)
	{
		return($value === '2.0' || $value === 2.0);
	}
}