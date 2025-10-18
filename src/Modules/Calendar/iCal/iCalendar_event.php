<?php

namespace App\Modules\Calendar\iCal;

class iCalendar_event extends iCalendar_component
{

	public $name = 'VEVENT';
	public $properties;
	public $mapping_arr = array(
		'CLASS' => array('component' => 'visibility', 'type' => 'string'),
		'DTSTART' => array('component' => array('date_start', 'time_start'), 'function' => 'iCalendar_event_dtstart', 'type' => 'datetime'),
		'DTEND' => array('component' => array('due_date', 'time_end'), 'function' => 'iCalendar_event_dtend', 'type' => 'datetime'),
		'DTSTAMP' => array('component' => array('date_start', 'time_start'), 'function' => 'iCalendar_event_dtstamp', 'type' => 'datetime'),
		'LOCATION' => array('component' => 'location', 'type' => 'string'),
		'ORGANIZER' => array('component' => 'activityid', 'function' => 'icalendar_event_organizer', 'type' => 'user'),
		'STATUS' => array('component' => 'activitystatus', 'type' => 'string'),
		'SUMMARY' => array('component' => 'subject', 'type' => 'string'),
		'PRIORITY' => array('component' => 'priority', 'type' => 'string'),
		'ATTENDEE' => array('component' => 'activityid', 'function' => 'iCalendar_event_attendee', 'type' => 'user'),
		'RESOURCES' => array('component' => array('location', 'activitystatus'), 'type' => 'string'),
		'DESCRIPTION' => array('component' => 'description', 'type' => 'string'),
	);
	public $field_mapping_arr = array(
		'priority' => 'taskpriority'
	);

	public function construct()
	{

		$this->valid_components = array('VALARM');

		$this->valid_properties = array(
			'CLASS' => RFC2445_OPTIONAL | RFC2445_ONCE,
			'CREATED' => RFC2445_OPTIONAL | RFC2445_ONCE,
			'DESCRIPTION' => RFC2445_OPTIONAL | RFC2445_ONCE,
			// Standard ambiguous here: in 4.6.1 it says that DTSTAMP in optional,
			// while in 4.8.7.2 it says it's REQUIRED. Go with REQUIRED.
			'DTSTAMP' => RFC2445_REQUIRED | RFC2445_ONCE,
			// Standard ambiguous here: in 4.6.1 it says that DTSTART in optional,
			// while in 4.8.2.4 it says it's REQUIRED. Go with REQUIRED.
			'DTSTART' => RFC2445_REQUIRED | RFC2445_ONCE,
			'GEO' => RFC2445_OPTIONAL | RFC2445_ONCE,
			'LAST-MODIFIED' => RFC2445_OPTIONAL | RFC2445_ONCE,
			'LOCATION' => RFC2445_OPTIONAL | RFC2445_ONCE,
			'ORGANIZER' => RFC2445_OPTIONAL | RFC2445_ONCE,
			'PRIORITY' => RFC2445_OPTIONAL | RFC2445_ONCE,
			'SEQUENCE' => RFC2445_OPTIONAL | RFC2445_ONCE,
			'STATUS' => RFC2445_OPTIONAL | RFC2445_ONCE,
			'SUMMARY' => RFC2445_OPTIONAL | RFC2445_ONCE,
			'TRANSP' => RFC2445_OPTIONAL | RFC2445_ONCE,
			// Standard ambiguous here: in 4.6.1 it says that UID in optional,
			// while in 4.8.4.7 it says it's REQUIRED. Go with REQUIRED.
			'UID' => RFC2445_REQUIRED | RFC2445_ONCE,
			'URL' => RFC2445_OPTIONAL | RFC2445_ONCE,
			'RECURRENCE-ID' => RFC2445_OPTIONAL | RFC2445_ONCE,
			'DTEND' => RFC2445_OPTIONAL | RFC2445_ONCE,
			'DURATION' => RFC2445_OPTIONAL | RFC2445_ONCE,
			'ATTACH' => RFC2445_OPTIONAL,
			'ATTENDEE' => RFC2445_OPTIONAL,
			'CATEGORIES' => RFC2445_OPTIONAL,
			'COMMENT' => RFC2445_OPTIONAL,
			'CONTACT' => RFC2445_OPTIONAL,
			'EXDATE' => RFC2445_OPTIONAL,
			'EXRULE' => RFC2445_OPTIONAL,
			'REQUEST-STATUS' => RFC2445_OPTIONAL,
			'RELATED-TO' => RFC2445_OPTIONAL,
			'RESOURCES' => RFC2445_OPTIONAL,
			'RDATE' => RFC2445_OPTIONAL,
			'RRULE' => RFC2445_OPTIONAL,
			RFC2445_XNAME => RFC2445_OPTIONAL
		);

		parent::construct();
	}

	public function invariant_holds()
	{
		// DTEND and DURATION must not appear together
		if (isset($this->properties['DTEND']) && isset($this->properties['DURATION'])) {
			return false;
		}


		if (isset($this->properties['DTEND']) && isset($this->properties['DTSTART'])) {

			if ($this->properties['DTEND'][0]->value <= $this->properties['DTSTART'][0]->value) {
				return false;
			}

			// DTEND and DTSTART must have the same value type
			if ($this->properties['DTEND'][0]->val_type != $this->properties['DTSTART'][0]->val_type) {
				return false;
			}
		}
		return true;
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
		$time = str_replace(':', '', $activity['time_end']);
		if (strlen($time) < 6) {
			while ((6 - strlen($time)) > 0) {
				$time .= '0';
			}
		}
		$components = str_replace('-', '', $activity['due_date']) . 'T' . $time . 'Z';
		$this->add_property("DTEND", $components);
		return true;
	}

	public function iCalendar_event_attendee($activity)
	{
		$adb = \App\Database\PearDatabase::getInstance();
		$result = $adb->pquery('SELECT * FROM u_yf_activity_invitation WHERE activityid=?', array($activity['id']));
		while ($row = $adb->getRow($result)) {
			if (!empty($row['email'])) {
				$this->add_property('ATTENDEE', 'mailto:' . $row['email'], ['CN' => \vtlib\Functions::getCRMRecordLabel($row['crmid'])]);
			}
		}
		return true;
	}

	public function icalendar_event_organizer($activity)
	{
		$email = \App\Fields\Email::getUserMail($activity['assigned_user_id']);
		$this->add_property('ORGANIZER', 'mailto:' . $email);
		return true;
	}
}