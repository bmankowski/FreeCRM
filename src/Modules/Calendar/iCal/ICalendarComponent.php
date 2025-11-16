<?php

namespace App\Modules\Calendar\iCal;

class ICalendarComponent {

	public $name = NULL;
	public $properties = NULL;
	public $components = NULL;
	public $valid_properties = NULL;
	public $valid_components = NULL;

	public function __construct()
	{
		$this->construct();
	}

	public function construct()
	{
		// Initialize the components array
		if (empty($this->components)) {
			$this->components = array();
			foreach ($this->valid_components as $name) {
				$this->components[$name] = array();
			}
		}
	}

	public function get_name()
	{
		return $this->name;
	}

	public function add_property($name, $value = NULL, $parameters = NULL)
	{

		// Uppercase first of all
		$name = strtoupper($name);
		// Are we trying to add a valid property?
		$xname = false;
		if (!isset($this->valid_properties[$name])) {
			// If not, is it an x-name as per RFC 2445?
			if (!rfc2445_is_xname($name)) {
				return false;
			}
			// Since this is an xname, all components are supposed to allow this property
			$xname = true;
		}

		// Create a property object of the correct class
		if ($xname) {
			$property = new iCalendar_property_x;
			$property->set_name($name);
		} else {
			$classname = 'iCalendar_property_' . strtolower(str_replace('-', '_', $name));
			$property = new $classname;
		}
		// If $value is NULL, then this property must define a default value.
		if ($value === NULL) {
			$value = $property->default_value();
			if ($value === NULL) {
				return false;
			}
		}

		// Set this property's parent component to ourselves, because some
		// properties behave differently according to what component they apply to.
		$property->set_parent_component($this->name);

		// Set parameters before value; this helps with some properties which
		// accept a VALUE parameter, and thus change their default value type.
		// The parameters must be valid according to property specifications
		if (!empty($parameters)) {
			foreach ($parameters as $paramname => $paramvalue) {
				if (!$property->set_parameter($paramname, $paramvalue)) {
					return false;
				}
			}

			// Some parameters interact among themselves (e.g. ENCODING and VALUE)
			// so make sure that after the dust settles, these invariants hold true
			if (!$property->invariant_holds()) {
				return false;
			}
		}

		// $value MUST be valid according to the property data type
		if (!$property->set_value($value)) {
			return false;
		}

		// If this property is restricted to only once, blindly overwrite value
		if (!$xname && $this->valid_properties[$name] & RFC2445_ONCE) {
			$this->properties[$name] = array($property);
		}

		// Otherwise add it to the instance array for this property
		else {
			$this->properties[$name][] = $property;
		}

		// Finally: after all these, does the component invariant hold?
		if (!$this->invariant_holds()) {
			// If not, completely undo the property addition
			array_pop($this->properties[$name]);
			if (empty($this->properties[$name])) {
				unset($this->properties[$name]);
			}
			return false;
		}

		return true;
	}

	public function add_component($component)
	{

		// With the detailed interface, you can add only components with this function
		if (!is_object($component) || !is_subclass_of($component, 'iCalendar_component')) {
			return false;
		}

		$name = $component->get_name();

		// Only valid components as specified by this component are allowed
		if (!in_array($name, $this->valid_components)) {
			return false;
		}

		// Add it
		$this->components[$name][] = $component;

		return true;
	}

	public function get_property_list($name)
	{
		
	}

	public function invariant_holds()
	{
		return true;
	}

	public function is_valid()
	{
		// If we have any child components, check that they are all valid
		if (!empty($this->components)) {
			foreach ($this->components as $component => $instances) {
				foreach ($instances as $number => $instance) {
					if (!$instance->is_valid()) {
						return false;
					}
				}
			}
		}
		// Finally, check the valid property list for any mandatory properties
		// that have not been set and do not have a default value
		foreach ($this->valid_properties as $property => $propdata) {
			if (($propdata & RFC2445_REQUIRED) && empty($this->properties[$property])) {
				$classname = 'iCalendar_property_' . strtolower(str_replace('-', '_', $property));
				$object = new $classname;
				if ($object->default_value() === NULL) {
					return false;
				}
				unset($object);
			}
		}

		return true;
	}

	public function serialize()
	{
		// Check for validity of the object
		if (!$this->is_valid()) {
			return false;
		}

		// Maybe the object is valid, but there are some required properties that
		// have not been given explicit values. In that case, set them to defaults.
		foreach ($this->valid_properties as $property => $propdata) {
			if (($propdata & RFC2445_REQUIRED) && empty($this->properties[$property])) {
				$this->add_property($property);
			}
		}

		// Start tag
		$string = rfc2445_fold('BEGIN:' . $this->name) . RFC2445_CRLF;
		// List of properties
		if (!empty($this->properties)) {
			foreach ($this->properties as $name => $properties) {
				foreach ($properties as $property) {
					$string .= $property->serialize();
				}
			}
		}
		// List of components
		if (!empty($this->components)) {
			foreach ($this->components as $name => $components) {
				foreach ($components as $component) {
					$string .= $component->serialize();
				}
			}
		}

		// End tag
		$string .= rfc2445_fold('END:' . $this->name) . RFC2445_CRLF;

		return $string;
	}

	public function assign_values($activity)
	{
		foreach ($this->mapping_arr as $key => $components) {
			if (!is_array($components['component']) && empty($components['function'])) {
				$this->add_property($key, $activity[$components['component']]);
			} else if (is_array($components['component']) && empty($components['function'])) {
				$component = '';
				foreach ($components['component'] as $comp) {
					if (!empty($component))
						$component .= ',';
					$component .= $activity[$comp];
				}
				$this->add_property($key, $component);
			} else if (!empty($components['function'])) {
				$function = $components['function'];
				$this->$function($activity);
			}
		}
		return true;
	}

	public function generateArray($ical_activity)
	{
		$currentUser = \App\User\CurrentUser::get();
		$activity = array();
		$activitytype = $ical_activity['TYPE'];
		if ($activitytype == 'VEVENT') {
			$modtype = 'Events';
		} else {
			$modtype = 'Calendar';
		}
		foreach ($this->mapping_arr as $key => $comp) {
			$type = $comp['type'];
			$component = $comp['component'];
			if (!is_array($component)) {
				if ($type != 'user') {
					if (isset($this->field_mapping_arr[$component])) {
						if (\App\Fields\Field::getFieldPermission($modtype, $this->field_mapping_arr[$component]))
							$activity[$this->field_mapping_arr[$component]] = $ical_activity[$key];
						else
							$activity[$this->field_mapping_arr[$component]] = '';
					} else {
						if (\App\Fields\Field::getFieldPermission($modtype, $component))
							$activity[$component] = $ical_activity[$key];
						else
							$activity[$component] = '';
					}
				}
			} else {
				$temp = $ical_activity[$key];
				$count = 0;
				if ($type == 'string') {
					$values = explode('\\,', $temp);
				} else if ($type == 'datetime' && !empty($temp)) {
					$values = $this->strtodatetime($temp);
				}
				foreach ($component as $index) {
					if (!isset($activity[$index])) {
						if (isset($this->field_mapping_arr[$index])) {
							if (\App\Fields\Field::getFieldPermission($modtype, $this->field_mapping_arr[$index]))
								$activity[$this->field_mapping_arr[$index]] = $values[$count];
							else
								$activity[$this->field_mapping_arr[$index]] = '';
						} else {
							if (\App\Fields\Field::getFieldPermission($modtype, $index))
								$activity[$index] = $values[$count];
							else
								$activity[$index] = '';
						}
					}
					$count++;
				}
				unset($values);
			}
		}
		if ($activitytype == 'VEVENT') {
			$activity['activitytype'] = 'Meeting';
			if (empty($activity['eventstatus'])) {
				$activity['eventstatus'] = 'PLL_PLANNED';
			}
			if (!empty($ical_activity['VALARM'])) {
				$temp = str_replace("P", '', $ical_activity['VALARM']['TRIGGER']);
				//if there is negative value then ignore it because in vtiger even though its negative or postiview we 
				//make reminder to be before the event
				$temp = str_replace("-", '', $temp);
				$durationTypeCharacters = array('W', 'D', 'T', 'H', 'M', 'S');
				$reminder_time = 0;
				foreach ($durationTypeCharacters as $durationType) {
					if (strpos($temp, $durationType) === false) {
						continue;
					}
					$parts = explode($durationType, $temp);
					$durationValue = $parts[0];
					$temp = $parts[1];
					$duration_type = $durationType;
					$duration = intval($durationValue);
					switch ($duration_type) {
						case 'W' :
							$reminder_time += 24 * 24 * 60 * $durationValue;
							break;
						case 'D' :
							$reminder_time += 24 * 60 * $durationValue;
							break;
						case 'T' :
							//Skip this symbol since its just indicates the start of time component
							break;
						case 'H' :
							$reminder_time += $duration * 60;
							break;
						case 'M' :
							$reminder_time = $duration;
							break;
					}
				}
				$activity['reminder_time'] = $reminder_time;
			}
		} else {
			$activity['activitytype'] = 'Task';
			if (empty($activity['activitystatus'])) {
				$activity['activitystatus'] = 'PLL_PLANNED';
			}
		}
		if ($activity['visibility'] == 'PUBLIC') {
			$activity['visibility'] = 'Public';
		}
		if ($activity['visibility'] == 'PRIVATE' || empty($activity['visibility'])) {
			$activity['visibility'] = 'Private';
		}
		if (array_key_exists('taskpriority', $activity)) {
			$priorityMap = array('1' => 'Low', '5' => 'Medium', '9' => 'High');
			$priorityval = $activity['taskpriority'];
			if (array_key_exists($priorityval, $priorityMap))
				$activity['taskpriority'] = $priorityMap[$priorityval];
		}
		if (!array_key_exists('visibility', $activity)) {
			$activity['visibility'] = ' ';
		}
		return $activity;
	}

	public function strtodatetime($date)
	{
		$date = preg_replace('/[A-Za-z_]*/', '', $date);
		$year = substr($date, 0, 4);
		$month = substr($date, 4, 2);
		$day = substr($date, 6, 2);
		$hours = substr($date, 8, 2);
		$minutes = substr($date, 10, 2);
		$seconds = substr($date, 12, 2);
		$datetime[] = $year . "-" . $month . "-" . $day;
		if (empty($hours))
			$hours = '00';
		if (empty($minutes))
			$minutes = '00';
		if (empty($seconds))
			$seconds = '00';
		$datetime[] = $hours . ":" . $minutes . ":" . $seconds;
		return $datetime;
	}
}