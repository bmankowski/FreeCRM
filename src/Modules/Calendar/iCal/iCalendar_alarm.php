<?php

namespace App\Modules\Calendar\iCal;

class iCalendar_alarm extends iCalendar_component
{

	public $name = 'VALARM';
	public $properties;
	public $mapping_arr = array(
		'TRIGGER' => array('component' => 'reminder_time', 'function' => 'iCalendar_event_trigger'),
	);

	public function construct()
	{

		$this->valid_components = array();
		$this->valid_properties = array(
			'TRIGGER' => RFC2445_OPTIONAL | RFC2445_ONCE,
			'DESCRIPTION' => RFC2445_OPTIONAL | RFC2445_ONCE,
			'ACTION' => RFC2445_OPTIONAL | RFC2445_ONCE,
			'X-WR-ALARMUID' => RFC2445_OPTIONAL | RFC2445_ONCE,
			RFC2445_XNAME => RFC2445_OPTIONAL
		);

		parent::construct();
	}

	public function iCalendar_event_trigger($activity)
	{
		$reminder_time = $activity['reminder_time'];
		if ($reminder_time > 60) {
			$reminder_time = round($reminder_time / 60);
			$reminder = $reminder_time . 'H';
		} else {
			$reminder = $reminder_time . 'M';
		}
		$this->add_property('ACTION', 'DISPLAY');
		$this->add_property('TRIGGER', 'PT' . $reminder);
		$this->add_property('DESCRIPTION', 'Reminder');
		return true;
	}
}