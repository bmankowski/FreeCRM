<?php

namespace App\Modules\Services\Models;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

class Module extends \App\Modules\Base\Models\Module
{

	/**
	 * Function to get list view query for popup window
	 * @param string $sourceModule Parent module
	 * @param string $field parent fieldname
	 * @param string $record parent id
	 * @param \App\QueryField\QueryGenerator $queryGenerator
	 */
	public function getQueryByModuleField($sourceModule, $field, $record, \App\QueryField\QueryGenerator $queryGenerator)
	{
		$supportedModulesList = array('Leads', 'Accounts', 'HelpDesk');
		if (($sourceModule == 'PriceBooks' && $field == 'priceBookRelatedList') || in_array($sourceModule, $supportedModulesList) || \App\Modules\Base\Models\Module::getInstance($sourceModule)->isInventory()) {
			$condition = ['and', ['vtiger_service.discontinued' => 1]];
			if ($sourceModule == 'PriceBooks' && $field == 'priceBookRelatedList') {
				$subQuery = (new \App\Db\Query())
					->select(['productid'])
					->from('vtiger_pricebookproductrel')
					->where(['pricebookid' => $record]);
				$condition [] = ['not in', 'vtiger_service.serviceid', $subQuery];
			}
			$queryGenerator->addNativeCondition($condition);
		}
	}
}
