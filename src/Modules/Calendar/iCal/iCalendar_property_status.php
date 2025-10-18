<?php

namespace App\Modules\Calendar\iCal;

class iCalendar_property_status extends iCalendar_property
{

	public $name = 'STATUS';
	public $val_type = RFC2445_TYPE_TEXT;

	public function construct()
	{
		$this->valid_parameters = array(
			RFC2445_XNAME => RFC2445_OPTIONAL
		);
	}
	/*    function is_valid_value($value) {
	  // This is case-sensitive
	  switch ($this->parent_component) {
	  case 'VEVENT':
	  $allowed = array('TENTATIVE', 'CONFIRMED', 'CANCELLED');
	  break;
	  case 'VTODO':
	  $allowed = array('NEEDS-ACTION', 'COMPLETED', 'IN-PROCESS', 'CANCELLED');
	  break;
	  case 'VJOURNAL':
	  $allowed = array('DRAFT', 'FINAL', 'CANCELLED');
	  break;
	  }
	  return in_array($value, $allowed);

	  }
	 */
}