<?php

namespace App\Modules\Accounts\Models;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
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
		if (($sourceModule === 'Accounts' && $field === 'account_id' && $record) || in_array($sourceModule, ['Campaigns', 'Products', 'Services'])) {
			if ($sourceModule === 'Campaigns' && $record) {
				$subQuery = (new \App\Db\Query())->select(['crmid'])->from('vtiger_campaign_records')->where(['campaignid' => $record]);
				$queryGenerator->addNativeCondition(['not in', 'vtiger_account.accountid', $subQuery]);
			} elseif ($sourceModule === 'Products' && $record) {
				$subQuery = (new \App\Db\Query())->select(['crmid'])->from('vtiger_seproductsrel')->where(['productid' => $record]);
				$queryGenerator->addNativeCondition(['not in', 'vtiger_account.accountid', $subQuery]);
			} elseif ($sourceModule === 'Services' && $record) {
				$subQuery = (new \App\Db\Query())->select(['relcrmid'])->from('vtiger_crmentityrel')->where(['crmid' => $record]);
				$secondSubQuery = (new \App\Db\Query())->select(['crmid'])->from('vtiger_crmentityrel')->where(['relcrmid' => $record]);
				$queryGenerator->addNativeCondition(['and', ['not in', 'vtiger_account.accountid', $subQuery], ['not in', 'vtiger_account.accountid', $secondSubQuery]]);
			} else {
				$queryGenerator->addNativeCondition(['<>', 'vtiger_account.accountid', 0]);
			}
		}
	}

	/**
	 * Function searches the records in the module, if parentId & parentModule
	 * is given then searches only those records related to them.
	 * @param string $searchValue - Search value
	 * @param int $parentId - parent recordId
	 * @param string $parentModule - parent module name
	 * @return array
	 */
	public function searchRecord($searchValue, $parentId = false, $parentModule = false, $relatedModule = false)
	{
		$matchingRecords = parent::searchRecord($searchValue, $parentId, $parentModule, $relatedModule);
		if (!empty($parentId) && !empty($parentModule)) {
			unset($matchingRecords[$relatedModule][$parentId]);
		}
		return $matchingRecords;
	}
}
