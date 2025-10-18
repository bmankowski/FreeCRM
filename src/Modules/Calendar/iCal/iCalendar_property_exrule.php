<?php

namespace App\Modules\Calendar\iCal;

class iCalendar_property_exrule extends iCalendar_property
{

	public $name = 'EXRULE';
	public $val_type = RFC2445_TYPE_RECUR;

	public function construct()
	{
		$this->valid_parameters = array(
			RFC2445_XNAME => RFC2445_OPTIONAL
		);
	}
}