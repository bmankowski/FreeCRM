<?php

namespace App\Modules\Calendar\iCal;

class iCalendar_property_resources extends iCalendar_property
{

	public $name = 'RESOURCES';
	public $val_type = RFC2445_TYPE_TEXT;
	public $val_multi = true;

	public function construct()
	{
		$this->valid_parameters = array(
			'ALTREP' => RFC2445_OPTIONAL | RFC2445_ONCE,
			'LANGUAGE' => RFC2445_OPTIONAL | RFC2445_ONCE,
			RFC2445_XNAME => RFC2445_OPTIONAL
		);
	}
}