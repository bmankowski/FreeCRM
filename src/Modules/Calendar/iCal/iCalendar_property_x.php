<?php

namespace App\Modules\Calendar\iCal;

class iCalendar_property_x extends iCalendar_property
{

	public $name = RFC2445_XNAME;
	public $val_type = NULL;

	public function construct()
	{
		$this->valid_parameters = array(
			'LANGUAGE' => RFC2445_OPTIONAL | RFC2445_ONCE,
			RFC2445_XNAME => RFC2445_OPTIONAL
		);
	}

	public function set_name($name)
	{

		$name = strtoupper($name);

		if (rfc2445_is_xname($name)) {
			$this->name = $name;
			return true;
		}

		return false;
	}
}