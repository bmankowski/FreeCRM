<?php

namespace FreeCRM\Modules\Vtiger\UiTypes;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

class UserReference extends Base
{

	/**
	 * Function to get the Template name for the current UI Type object
	 * @return string - Template Name
	 */
	public function getTemplateName()
	{
		return 'uitypes/Reference.tpl';
	}

	/**
	 * Function to get the display value in detail view
	 * @param <Integer> crmid of record
	 * @return string
	 */
	public function getEditViewDisplayValue($value, $record = false)
	{
		if ($value) {
			$userName = \App\Fields\Owner::getLabel($value);
			return $userName;
		}
	}

	/**
	 * Function to get display value
	 * @param string $value
	 * @param <Number> $recordId
	 * @return string display value
	 */
	public function getDisplayValue($value, $recordId = false, $recordInstance = false, $rawText = false)
	{
		$displayValue = $this->getEditViewDisplayValue($value);
		$currentUserModel = \FreeCRM\Modules\Users\Models\Record::getCurrentUserModel();
		if ($currentUserModel->isAdminUser() && $rawText === false) {
			$recordModel = \FreeCRM\Modules\Users\Models\Record::getCleanInstance('Users');
			$recordModel->set('id', $value);
			return '<a href="' . $recordModel->getDetailViewUrl() . '">' . \vtlib\Functions::textLength($displayValue) . '</a>';
		}
		return $displayValue;
	}
}
