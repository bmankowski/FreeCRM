<?php

namespace App\Modules\Base\UiTypes;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

class Reminder extends BaseUiType
{

	/**
	 * Function to get the Template name for the current UI Type object
	 * @return string - Template Name
	 */
	public function getTemplateName()
	{
		return 'uitypes/Reminder.tpl';
	}

	/**
	 * Function to get the Detailview template name for the current UI Type Object
	 * @return string - Template Name
	 */
	public function getDetailViewTemplateName()
	{
		return 'uitypes/ReminderDetailView.tpl';
	}

	/**
	 * Function to get the Display Value, for the current field type with given DB Insert Value
	 * @param <Object> $value
	 * @return <Object>
	 */
	public function getDisplayValue($value, $record = false, $recordInstance = false, $rawText = false)
	{
		$reminder_value = '';
		$reminder_time = $this->getEditViewDisplayValue($value);
		if (!is_array($reminder_time)) {
			return $reminder_value;
		}
		if (!empty($reminder_time[0])) {
			$reminder_value = $reminder_time[0] . ' ' . \App\Runtime\Vtiger_Language_Handler::translate('LBL_DAYS');
		}
		if (!empty($reminder_time[1])) {
			$reminder_value = $reminder_value . ' ' . $reminder_time[1] . ' ' . \App\Runtime\Vtiger_Language_Handler::translate('LBL_HOURS');
		}
		if (!empty($reminder_time[2])) {
			$reminder_value = $reminder_value . ' ' . $reminder_time[2] . ' ' . \App\Runtime\Vtiger_Language_Handler::translate('LBL_MINUTES');
		}

		return $reminder_value;
	}

	/**
	 * Function to get the display value in edit view
	 * @param $value
	 * @return converted value
	 */
	public function getEditViewDisplayValue($value, $record = false)
	{
		if ($value === '' || $value === null || $value === false) {
			return '';
		}
		$minutes = (int) $value;
		if ($minutes <= 0) {
			return '';
		}
		$rem_days = (int) floor($minutes / (24 * 60));
		$rem_hrs = (int) floor(($minutes - $rem_days * 24 * 60) / 60);
		$rem_min = (int) (($minutes - ($rem_days * 24 * 60)) % 60);
		return [$rem_days, $rem_hrs, $rem_min];
	}
}
