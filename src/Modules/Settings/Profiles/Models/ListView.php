<?php

namespace App\Modules\Settings\Profiles\Models;


/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * ********************************************************************************** */

use App\Modules\Vtiger\Models\ListView as Vtiger_ListView_Model;
class ListView extends \Settings_Vtiger_ListView_Model
{

	public function getBasicListQuery()
	{
		$query = parent::getBasicListQuery();
		$query->where(['directly_related_to_role' => 0]);
		return $query;
	}
}
