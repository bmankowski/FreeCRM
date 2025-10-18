<?php

namespace App\Modules\Calendar\iCal;

class iCalendar_property_class extends iCalendar_property
{

	public $name = 'CLASS';
	public $val_type = RFC2445_TYPE_TEXT;
	public $val_default = 'PUBLIC';

	public function construct()
	{
		$this->valid_parameters = array(
			RFC2445_XNAME => RFC2445_OPTIONAL
		);
	}

	public function is_valid_value($value)
	{
		$value = strtoupper($value);
		// If this is not an xname, it is case-sensitive
		return ($value === 'PUBLIC' || $value === 'PRIVATE' || $value === 'CONFIDENTIAL' || rfc2445_is_xname(strtoupper($value)));
	}
}