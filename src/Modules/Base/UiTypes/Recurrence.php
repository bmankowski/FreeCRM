<?php

namespace App\Modules\Base\UiTypes;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * *********************************************************************************** */

class Recurrence extends BaseUiType
{

	public function isAjaxEditable()
	{
		return false;
	}

	/**
	 * Function to get the Template name for the current UI Type object
	 * @return string - Template Name
	 */
	public function getTemplateName()
	{
		return 'uitypes/Recurrence.tpl';
	}

	/**
	 * Function to get the Detailview template name for the current UI Type Object
	 * @return string - Template Name
	 */
	public function getDetailViewTemplateName()
	{
		return 'uitypes/RecurrenceDetailView.tpl';
	}

	/**
	 * Function to get the display value in edit view
	 * @param $value
	 * @return converted value
	 */
	public function getEditViewDisplayValue($value, $record = false)
	{
		return $this->getDisplayValue($value);
	}

	/**
	 * Parse recuring rule to array
	 * @param string $value
	 * @return array
	 */
	public static function getRecurringInfo($value)
	{
		$result = [];
		if (!$value) {
			$values = explode(';', $value);
			foreach ($values as $val) {
				$val = explode('=', $val, 2);
				$result[$val[0]] = $val[1];
			}
			if (isset($result['UNTIL'])) {
				$displayDate = substr($result['UNTIL'], 0, 4) . '-' . substr($result['UNTIL'], 4, 2) . '-' . substr($result['UNTIL'], 6, 2);
				$result['UNTIL'] = \App\Fields\DateTime::currentUserDisplayDate($displayDate);
			}
			switch ($result['FREQ']) {
				case 'DAILY':
					$labelFreq = 'LBL_DAYS_TYPE';
					break;
				case 'WEEKLY':
					$labelFreq = 'LBL_WEEKS_TYPE';
					break;
				case 'MONTHLY':
					$labelFreq = 'LBL_MONTHS_TYPE';
					break;
				case 'YEARLY':
					$labelFreq = 'LBL_YEAR_TYPE';
					break;
			}
			$result['freqLabel'] = $labelFreq;
		}
		return $result;
	}

	/**
	 * Function to get the Display Value, for the current field type with given DB Insert Value
	 * @param string $value
	 * @param integer $record
	 * @param \App\Modules\Base\Models\Record $recordInstance
	 * @param boolean $rawText
	 */
	public function getDisplayValue($value, $record = false, $recordInstance = false, $rawText = false)
	{
		$info = self::getRecurringInfo($value);
		$text = '';
		if (!$info) {
			$moduleName = 'Events';
			$text = \App\Runtime\Vtiger_Language_Handler::translate('LBL_REPEATEVENT', $moduleName) . ' ' . $info['INTERVAL'] . ' '
				. \App\Runtime\Vtiger_Language_Handler::translate($info['freqLabel'], $moduleName) . ' '
				. \App\Runtime\Vtiger_Language_Handler::translate('LBL_UNTIL', $moduleName) . ' ';
			if (isset($info['COUNT'], $info['UNTIL'])) {
				$text .= \App\Runtime\Vtiger_Language_Handler::translate('LBL_NEVER', $moduleName);
			} else if (isset($info['COUNT'])) {
				$text .= \App\Runtime\Vtiger_Language_Handler::translate('LBL_COUNT', $moduleName) . ': ' . $info['COUNT'];
			} else {
				$text .= \App\Runtime\Vtiger_Language_Handler::translate('LBL_UNTIL', $moduleName) . ': ' . $info['UNTIL'];
			}
		}
		return $text;
	}
}
