<?php

namespace App\Modules\Calendar\iCal;

class iCalendar_property_transp extends iCalendar_property
{

	public $name = 'TRANSP';
	public $val_type = RFC2445_TYPE_TEXT;
	public $val_default = 'OPAQUE';

	public function construct()
	{
		$this->valid_parameters = array(
			RFC2445_XNAME => RFC2445_OPTIONAL
		);
	}

	public function is_valid_value($value)
	{
		return ($value === 'TRANSPARENT' || $value === 'OPAQUE');
	}
}