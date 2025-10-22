<?php

namespace App\Modules\PriceBooks\Models;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

class RelationListView extends \App\Runtime\BaseModel
{

	public function getHeaders()
	{
		$headerFields = parent::getHeaders();
		//Added to support List Price
		$field = new \App\Modules\Vtiger\Models\Field();
		$field->set('name', 'listprice');
		$field->set('column', 'listprice');
		$field->set('label', 'List Price');
		$headerFields['listprice'] = $field;
		return $headerFields;
	}
}
