<?php

namespace FreeCRM\Modules\Calendar\UiTypes;

/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * ********************************************************************************** */

class Picklist extends UIType
{

	public function getListSearchTemplateName()
	{

		$fieldName = $this->get('field')->get('name');

		if ($fieldName == 'activitystatus') {
			return 'uitypes/StatusPickListFieldSearchView.tpl';
		} else if ($fieldName == 'activitytype') {
			return 'uitypes/ActivityPicklistFieldSearchView.tpl';
		}
		return parent::getListSearchTemplateName();
	}
}
