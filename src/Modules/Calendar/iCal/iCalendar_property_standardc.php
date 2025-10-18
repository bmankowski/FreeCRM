<?php

namespace App\Modules\Calendar\iCal;

class iCalendar_property_standardc extends iCalendar_property
{

	public $name = 'STANDARDC';
	public $val_type = RFC2445_TYPE_INTEGER;

	public function construct()
	{
		$this->valid_parameters = array(
			RFC2445_XNAME => RFC2445_OPTIONAL
		);
	}
}