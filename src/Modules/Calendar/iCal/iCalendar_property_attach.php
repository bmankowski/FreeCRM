<?php

namespace App\Modules\Calendar\iCal;

class iCalendar_property_attach extends iCalendar_property
{

	public $name = 'ATTACH';
	public $val_type = RFC2445_TYPE_URI;

	public function construct()
	{
		$this->valid_parameters = array(
			'FMTTYPE' => RFC2445_OPTIONAL | RFC2445_ONCE,
			'ENCODING' => RFC2445_OPTIONAL | RFC2445_ONCE,
			'VALUE' => RFC2445_OPTIONAL | RFC2445_ONCE,
			RFC2445_XNAME => RFC2445_OPTIONAL
		);
	}

	public function invariant_holds()
	{
		if (isset($this->parameters['ENCODING']) && !isset($this->parameters['VALUE'])) {
			return false;
		}
		if (isset($this->parameters['VALUE']) && !isset($this->parameters['ENCODING'])) {
			return false;
		}

		return true;
	}

	public function is_valid_parameter($parameter, $value)
	{

		$parameter = strtoupper($parameter);

		if (!parent::is_valid_parameter($parameter, $value)) {
			return false;
		}

		if ($parameter === 'ENCODING' && strtoupper($value) != 'BASE64') {
			return false;
		}

		if ($parameter === 'VALUE' && strtoupper($value) != 'BINARY') {
			return false;
		}

		return true;
	}
}