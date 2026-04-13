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

class Integer extends BaseUiType
{

	/**
	 * Function to get the Template name for the current UI Type object
	 * @return string - Template Name
	 */
	public function getTemplateName()
	{
		return 'uitypes/Number.tpl';
	}

	/**
	 * @param mixed $value
	 * @param \App\Modules\Base\Models\Record|false $recordModel
	 * @return mixed
	 */
	public function getDBValue($value, $recordModel = false)
	{
		// BaseUiType turns null into ''; empty string for INT is inserted as NULL in DB — breaks NOT NULL columns (e.g. Documents.filesize).
		if ($value === null) {
			return 0;
		}
		return parent::getDBValue($value, $recordModel);
	}
}
