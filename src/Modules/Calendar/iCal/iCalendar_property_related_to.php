<?php

namespace App\Modules\Calendar\iCal;

class iCalendar_property_related_to extends iCalendar_property
{

	public $name = 'RELATED-TO';
	public $val_type = RFC2445_TYPE_TEXT;

	public function construct()
	{
		$this->valid_parameters = array(
			'RELTYPE' => RFC2445_OPTIONAL | RFC2445_ONCE,
			RFC2445_XNAME => RFC2445_OPTIONAL
		);
	}
}