<?php

namespace App\Modules\Calendar\iCal;

class iCalendar_property_sequence extends iCalendar_property
{

	public $name = 'SEQUENCE';
	public $val_type = RFC2445_TYPE_INTEGER;
	public $val_default = 0;

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
		$value = intval($value);
		return ($value >= 0);
	}
}