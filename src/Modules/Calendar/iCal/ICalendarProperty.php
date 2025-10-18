<?php

namespace App\Modules\Calendar\iCal;

class ICalendarProperty {

	// Properties can have parameters, but cannot have other properties or components

	public $parent_component = NULL;
	public $value = NULL;
	public $parameters = NULL;
	public $valid_parameters = NULL;
	// These are common for 95% of properties, so define them here and override as necessary
	public $val_multi = false;
	public $val_default = NULL;

	public function __construct()
	{
		$this->construct();
	}

	public function construct()
	{
		$this->parameters = array();
	}

	// If some property needs extra care with its parameters, override this
	// IMPORTANT: the parameter name MUST BE CAPITALIZED!
	public function is_valid_parameter($parameter, $value)
	{

		if (is_array($value)) {
			if (!iCalendar_parameter::multiple_values_allowed($parameter)) {
				return false;
			}
			foreach ($value as $item) {
				if (!iCalendar_parameter::is_valid_value($this, $parameter, $item)) {
					return false;
				}
			}
			return true;
		}

		return iCalendar_parameter::is_valid_value($this, $parameter, $value);
	}

	public function invariant_holds()
	{
		return true;
	}

	// If some property is very picky about its values, it should do the work itself
	// Only data type validation is done here
	public function is_valid_value($value)
	{
		if (is_array($value)) {
			if (!$this->val_multi) {
				return false;
			} else {
				foreach ($value as $oneval) {
					if (!rfc2445_is_valid_value($oneval, $this->val_type)) {
						return false;
					}
				}
			}
			return true;
		}
		return rfc2445_is_valid_value($value, $this->val_type);
	}

	public function default_value()
	{
		return $this->val_default;
	}

	public function set_parent_component($componentname)
	{
		if (class_exists('iCalendar_' . strtolower(substr($componentname, 1)))) {
			$this->parent_component = strtoupper($componentname);
			return true;
		}

		return false;
	}

	public function set_value($value)
	{
		if ($this->is_valid_value($value)) {
			// This transparently formats any value type according to the iCalendar specs
			if (is_array($value)) {
				foreach ($value as $key => $item) {
					$value[$key] = rfc2445_do_value_formatting($item, $this->val_type);
				}
				$this->value = implode(',', $value);
			} else {
				$this->value = rfc2445_do_value_formatting($value, $this->val_type);
			}

			return true;
		}
		return false;
	}

	public function get_value()
	{
		// First of all, assume that we have multiple values
		$valarray = explode('\\,', $this->value);

		// Undo transparent formatting
		$replace_function = create_function('$a', 'return rfc2445_undo_value_formatting($a, ' . $this->val_type . ');');
		$valarray = array_map($replace_function, $valarray);

		// Now, if this property cannot have multiple values, don't return as an array
		if (!$this->val_multi) {
			return $valarray[0];
		}

		// Otherwise return an array even if it has one element, for uniformity
		return $valarray;
	}

	public function set_parameter($name, $value)
	{

		// Uppercase
		$name = strtoupper($name);

		// Are we trying to add a valid parameter?
		$xname = false;
		if (!isset($this->valid_parameters[$name])) {
			// If not, is it an x-name as per RFC 2445?
			if (!rfc2445_is_xname($name)) {
				return false;
			}
			// No more checks -- all components are supposed to allow x-name parameters
			$xname = true;
		}

		if (!$this->is_valid_parameter($name, $value)) {
			return false;
		}

		if (is_array($value)) {
			foreach ($value as $key => $element) {
				$value[$key] = iCalendar_parameter::do_value_formatting($name, $element);
			}
		} else {
			$value = iCalendar_parameter::do_value_formatting($name, $value);
		}

		$this->parameters[$name] = $value;

		// Special case: if we just changed the VALUE parameter, reflect this
		// in the object's status so that it only accepts correct type values
		if ($name == 'VALUE') {
			$this->val_type = constant('RFC2445_TYPE_' . str_replace('-', '_', $value));
		}

		return true;
	}

	public function get_parameter($name)
	{

		// Uppercase
		$name = strtoupper($name);

		if (isset($this->parameters[$name])) {
			// If there are any double quotes in the value, invisibly strip them
			if (is_array($this->parameters[$name])) {
				foreach ($this->parameters[$name] as $key => $value) {
					if (substr($value, 0, 1) == '"') {
						$this->parameters[$name][$key] = substr($value, 1, strlen($value) - 2);
					}
				}
				return $this->parameters[$name];
			} else {
				if (substr($this->parameters[$name], 0, 1) == '"') {
					return substr($this->parameters[$name], 1, strlen($this->parameters[$name]) - 2);
				}
			}
		}

		return NULL;
	}

	public function serialize()
	{
		$string = $this->name;

		if (!empty($this->parameters)) {
			foreach ($this->parameters as $name => $value) {
				$string .= ';' . $name . '=';
				if (is_array($value)) {
					$string .= implode(',', $value);
				} else {
					$string .= $value;
				}
			}
		}

		$string .= ':' . $this->value;

		return rfc2445_fold($string) . RFC2445_CRLF;
	}
}