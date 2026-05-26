<?php

namespace App\Modules\Events\Models;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

/**
 * Events field model — activity date/time behavior lives in Calendar\Models\Field.
 */
class Field extends \App\Modules\Calendar\Models\Field
{

	public function get($propertyName)
	{
		if ($propertyName === 'label' && $this->getName() === 'due_date') {
			return 'End Date & Time';
		}
		return parent::get($propertyName);
	}
}
