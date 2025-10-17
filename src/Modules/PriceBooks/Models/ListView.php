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

class ListView extends \App\Modules\Vtiger\Models\ListView
{

	/**
	 * Function to get the list view entries
	 * @param \App\Modules\Vtiger\Models\Paging $pagingModel
	 * @return array - Associative array of record id mapped to \App\Modules\Vtiger\Models\Record instance.
	 */
	public function getListViewEntries(\App\Modules\Vtiger\Models\Paging $pagingModel)
	{
		if ($this->get('src_field') === 'productsRelatedList') {
			$pagingModel->set('limit', 'no_limit');
		}
		$this->getQueryGenerator()->currencyId = $this->get('currency_id');
		return parent::getListViewEntries($pagingModel);
	}
}
