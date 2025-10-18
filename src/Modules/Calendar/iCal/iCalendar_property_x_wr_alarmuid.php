<?php

namespace App\Modules\Calendar\iCal;

class iCalendar_property_x_wr_alarmuid extends iCalendar_property
{

	public $name = 'X_WR_ALARMUID';
	public $val_type = RFC2445_TYPE_TEXT;

	public function construct()
	{
		$this->valid_parameters = array(
			RFC2445_XNAME => RFC2445_OPTIONAL
		);
	}
}