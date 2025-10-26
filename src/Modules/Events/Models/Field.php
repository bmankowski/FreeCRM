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
 * Events Field Model Class
 */
class Field extends \App\Modules\Base\Models\Field
{

	public function get($propertyName)
	{
		if (property_exists($this, $propertyName)) {
			$fieldName = $this->getName();
			if ($propertyName == 'label' && $fieldName == 'due_date') {
				return 'End Date & Time';
			}
			return $this->$propertyName;
		}
		return null;
	}

	/**
	 * Customize the display value for detail view.
	 */
	public function getDisplayValue($value, $record = false, $recordInstance = false, $rawText = false)
	{
		if ($recordInstance) {
			if ($this->getName() == 'due_date') {
				$displayValue = $value . ' ' . $recordInstance->get('time_end');
				$value = $this->getUITypeModel()->getDisplayValue($displayValue);
				list($endDate, $endTime, $meridiem) = explode(' ', $value);
				return $endDate . ' ' . $endTime . ' ' . $meridiem;
			}
		}
		return parent::getDisplayValue($value, $record, $recordInstance, $rawText);
	}
}
