<?php

namespace App\Modules\Calendar\iCal;

class iCalendar_timezone extends iCalendar_component
{

	public $name = 'VTIMEZONE';
	public $properties;

	public function construct()
	{
		$this->valid_components = array();
		$this->valid_properties = array(
			'TZID' => RFC2445_REQUIRED | RFC2445_ONCE,
			'LAST-MODIFIED' => RFC2445_OPTIONAL | RFC2445_ONCE,
			'TZURL' => RFC2445_OPTIONAL | RFC2445_ONCE,
			'STANDARDC' => RFC2445_OPTIONAL,
			'DAYLIGHTC' => RFC2445_OPTIONAL,
			'TZOFFSETFROM' => RFC2445_OPTIONAL | RFC2445_ONCE,
			'TZOFFSETTO' => RFC2445_OPTIONAL | RFC2445_ONCE,
			'X-PROP' => RFC2445_OPTIONAL
		);

		parent::construct();
	}
}