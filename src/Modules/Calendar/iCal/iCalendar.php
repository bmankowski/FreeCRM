<?php

namespace App\Modules\Calendar\iCal;

class iCalendar extends iCalendar_component
{

	public $name = 'VCALENDAR';

	public function construct()
	{
		$this->valid_properties = array(
			'CALSCALE' => RFC2445_OPTIONAL | RFC2445_ONCE,
			'METHOD' => RFC2445_OPTIONAL | RFC2445_ONCE,
			'PRODID' => RFC2445_REQUIRED | RFC2445_ONCE,
			'VERSION' => RFC2445_REQUIRED | RFC2445_ONCE,
			RFC2445_XNAME => RFC2445_OPTIONAL
		);

		$this->valid_components = array(
			'VEVENT', 'VTODO', 'VTIMEZONE'
		);
		parent::construct();
	}
}