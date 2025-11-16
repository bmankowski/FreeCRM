<?php

namespace App\Modules\Calendar\iCal;

class iCalendar_property_prodid extends iCalendar_property
{

	public $name = 'PRODID';
	public $val_type = RFC2445_TYPE_TEXT;
	public $val_default = NULL;

	public function construct()
	{
		$this->val_default = '-//YetiForce CRM//YetiForce CRM ' . \App\Core\Version::get() . '//EN';

		$this->valid_parameters = array(
			RFC2445_XNAME => RFC2445_OPTIONAL
		);
	}
}