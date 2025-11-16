<?php

namespace App\Modules\Products\Models;

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
	 * @param boolean $skipSelected
	 */
	public function getQueryByModuleField($sourceModule, $field, $record, \App\QueryField\QueryGenerator $queryGenerator)
	{
		$supportedModulesList = array($this->getName(), 'Vendors', 'Leads', 'Accounts');
		if (($sourceModule == 'PriceBooks' && $field == 'priceBookRelatedList') || in_array($sourceModule, $supportedModulesList) || \App\Modules\Base\Models\Module::getInstance($sourceModule)->isInventory()) {
			$condition = ['and', ['vtiger_products.discontinued' => 1]];
			if ($sourceModule === $this->getName()) {
				$subQuery = (new \App\Db\Query())
					->select(['productid'])
					->from('vtiger_seproductsrel')
					->where(['setype' => $sourceModule]);
				$condition [] = ['not in', 'vtiger_products.productid', $subQuery];
				$subQuery = (new \App\Db\Query())
					->select(['crmid'])
					->from('vtiger_seproductsrel')
					->where(['productid' => $record]);
				$condition [] = ['not in', 'vtiger_products.productid', $subQuery];
				$condition [] = ['<>', 'vtiger_products.productid', $record];
			} elseif ($sourceModule === 'PriceBooks') {
				$subQuery = (new \App\Db\Query())
					->select(['productid'])
					->from('vtiger_pricebookproductrel')
					->where(['pricebookid' => $record]);
				$condition [] = ['not in', 'vtiger_products.productid', $subQuery];
			} elseif ($sourceModule === 'Vendors') {
				$condition [] = ['<>', 'vtiger_products.vendor_id', $record];
			}
			$queryGenerator->addNativeCondition($condition);
		}
	}

	/**
	 * Function to get Specific Relation Query for this Module
	 * @param <type> $relatedModule
	 * @return <type>
	 */
	public function getSpecificRelationQuery($relatedModule)
	{
		if ($relatedModule === 'Leads') {
			$specificQuery = 'AND vtiger_leaddetails.converted = 0';
			return $specificQuery;
		}
		return parent::getSpecificRelationQuery($relatedModule);
	}

	/**
	 * Function to get prices for specified products with specific currency
	 * @param <Integer> $currenctId
	 * @param <Array> $productIdsList
	 * @return <Array>
	 */
	public function getPricesForProducts($currencyId, $productIdsList)
	{
		return \App\Utils\InventoryUtils::getPricesForProducts($currencyId, $productIdsList, $this->getName());
	}

	/**
	 * Function to check whether the module is summary view supported
	 * @return boolean - true/false
	 */
	public function isSummaryViewSupported()
	{
		return false;
	}

	/**
	 * Function searches the records in the module, if parentId & parentModule
	 * is given then searches only those records related to them.
	 * @param string $searchValue - Search value
	 * @param <Integer> $parentId - parent recordId
	 * @param string $parentModule - parent module name
	 * @return <Array of \App\Modules\Base\Models\Record>
	 */
	public function searchRecord($searchValue, $parentId = false, $parentModule = false, $relatedModule = false)
	{
		if (!empty($searchValue) && empty($parentId) && empty($parentModule) && (in_array($relatedModule, \App\Utils\Utils::getInventoryModules()))) {
			$matchingRecords = \App\Modules\Products\Models\Record::getSearchResult($searchValue, $this->getName());
		} else {
			return parent::searchRecord($searchValue);
		}

		return $matchingRecords;
	}
}
