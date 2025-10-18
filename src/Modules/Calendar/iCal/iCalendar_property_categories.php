<?php

namespace App\Modules\Calendar\iCal;

class iCalendar_property_categories extends iCalendar_property
{

	public $name = 'CATEGORIES';
	public $val_type = RFC2445_TYPE_TEXT;
	public $val_multi = true;

	public function construct()
	{
		$this->valid_parameters = array(
			'LANGUAGE' => RFC2445_OPTIONAL | RFC2445_ONCE,
			RFC2445_XNAME => RFC2445_OPTIONAL
		);
	}
}