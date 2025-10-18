<?php

namespace App\Modules\Calendar\iCal;

class iCalendar_todo extends iCalendar_component
{

	public $name = 'VTODO';
	public $properties;
	public $mapping_arr = array(
		'DESCRIPTION' => array('component' => 'description', 'type' => 'string'),
		//'DTSTAMP'		=>	array('component'=>array('date_start','time_start'),'function'=>'iCalendar_event_dtstamp','type'=>'datetime'),
		'DTSTART' => array('component' => array('date_start', 'time_start'), 'function' => 'iCalendar_event_dtstart', 'type' => 'datetime'),
		'DUE' => array('component' => array('due_date'), 'function' => 'iCalendar_event_dtend', 'type' => 'datetime'),
		'STATUS' => array('component' => 'status', 'type' => 'string'),
		'SUMMARY' => array('component' => 'subject', 'type' => 'string'),
		'PRIORITY' => array('component' => 'priority', 'type' => 'string'),
		'RESOURCES' => array('component' => array('status'), 'type' => 'string'),
	);
	public $field_mapping_arr = array(
		'status' => 'activitystatus',
		'priority' => 'taskpriority'
	);

	public function construct()
	{

		$this->valid_components = array();
		$this->valid_properties = array(
			'CLASS' => RFC2445_OPTIONAL | RFC2445_ONCE,
			'COMPLETED' => RFC2445_OPTIONAL | RFC2445_ONCE,
			'CREATED' => RFC2445_OPTIONAL | RFC2445_ONCE,
			'DESCRIPTION' => RFC2445_OPTIONAL | RFC2445_ONCE,
			'DTSTAMP' => RFC2445_OPTIONAL | RFC2445_ONCE,
			'DTSTART' => RFC2445_OPTIONAL | RFC2445_ONCE,
			'GEO' => RFC2445_OPTIONAL | RFC2445_ONCE,
			'LAST-MODIFIED' => RFC2445_OPTIONAL | RFC2445_ONCE,
			'LOCATION' => RFC2445_OPTIONAL | RFC2445_ONCE,
			'ORGANIZER' => RFC2445_OPTIONAL | RFC2445_ONCE,
			'PERCENT' => RFC2445_OPTIONAL | RFC2445_ONCE,
			'PRIORITY' => RFC2445_OPTIONAL | RFC2445_ONCE,
			'RECURID' => RFC2445_OPTIONAL | RFC2445_ONCE,
			'SEQUENCE' => RFC2445_OPTIONAL | RFC2445_ONCE,
			'STATUS' => RFC2445_OPTIONAL | RFC2445_ONCE,
			'SUMMARY' => RFC2445_OPTIONAL | RFC2445_ONCE,
			'UID' => RFC2445_OPTIONAL | RFC2445_ONCE,
			'URL' => RFC2445_OPTIONAL | RFC2445_ONCE,
			'DUE' => RFC2445_OPTIONAL | RFC2445_ONCE,
			'DURATION' => RFC2445_OPTIONAL | RFC2445_ONCE,
			'ATTACH' => RFC2445_OPTIONAL,
			'ATTENDEE' => RFC2445_OPTIONAL,
			'CATEGORIES' => RFC2445_OPTIONAL,
			'COMMENT' => RFC2445_OPTIONAL,
			'CONTACT' => RFC2445_OPTIONAL,
			'EXDATE' => RFC2445_OPTIONAL,
			'EXRULE' => RFC2445_OPTIONAL,
			'RSTATUS' => RFC2445_OPTIONAL,
			'RELATED' => RFC2445_OPTIONAL,
			'RESOURCES' => RFC2445_OPTIONAL,
			'RDATE' => RFC2445_OPTIONAL,
			'RRULE' => RFC2445_OPTIONAL,
			'XPROP' => RFC2445_OPTIONAL
		);

		parent::construct();
	}

	public function iCalendar_event_dtstamp($activity)
	{
		$components = gmdate('Ymd', strtotime($activity['date_start'] . " " . $activity['time_start'])) . "T" . gmdate('His', strtotime($activity['date_start'] . " " . $activity['time_start'])) . "Z";
		$this->add_property("DTSTAMP", $components);
		return true;
	}

	public function iCalendar_event_dtstart($activity)
	{
		$time = str_replace(':', '', $activity['time_start']);
		if (strlen($time) < 6) {
			while ((6 - strlen($time)) > 0) {
				$time .= '0';
			}
		}
		$components = str_replace('-', '', $activity['date_start']) . 'T' . $time . 'Z';
		$this->add_property("DTSTART", $components);
		return true;
	}

	public function iCalendar_event_dtend($activity)
	{
		$components = str_replace('-', '', $activity['due_date']) . 'T000000Z';
		$this->add_property("DUE", $components);
		return true;
	}
}