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

class Theme extends BaseUiType
{

	/**
	 * Function to get the Template name for the current UI Type object
	 * @return string - Template Name
	 */
	public function getTemplateName()
	{
		return 'uitypes/Theme.tpl';
	}

	/**
	 * Function to get the Display Value, for the current field type with given DB Insert Value
	 * @param object $value
	 * @return object
	 */
	public function getDisplayValue($value, $record = false, $recordInstance = false, $rawText = false)
	{
		$allSkins = \App\Runtime\Vtiger_Theme::getAllSkins();
		$skinName = (string) $value;
		if (empty($skinName) || !isset($allSkins[$skinName])) {
			$skinName = \App\Runtime\CRM_Viewer::DEFAULTTHEME;
		}
		$skinColor = $allSkins[$skinName] ?? reset($allSkins);
		$value = ucfirst($skinName);
		return "<div class='col-md-4' style='width:230px; background-color:$skinColor;' title='$value'>&nbsp;</div>";
	}
}
