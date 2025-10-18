<?php

namespace App\Modules\Calendar\iCal;

class iCalendar_property_tzoffsetto extends iCalendar_property
{

	public $name = 'TZOFFSETTO';
	public $val_type = RFC2445_TYPE_TEXT;

	public function construct()
	{
		$this->valid_parameters = array(
			RFC2445_XNAME => RFC2445_OPTIONAL
		);
	}
}