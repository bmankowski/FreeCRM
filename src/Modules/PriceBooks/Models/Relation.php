<?php

namespace App\Modules\PriceBooks\Models;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * *********************************************************************************** */

class Relation extends \App\Modules\Base\Models\Relation
{

	/**
	 * Function to add PriceBook-Products/Services Relation
	 * @param int $sourceRecordId
	 * @param int $destinationRecordId
	 * @param int $listPrice
	 */
	public function addListPrice($sourceRecordId, $destinationRecordId, $listPrice)
	{
		$sourceModuleName = $this->getParentModuleModel()->get('name');

		$priceBookModel = \App\Modules\Base\Models\Record::getInstanceById($sourceRecordId, $sourceModuleName);
		$priceBookModel->updateListPrice($destinationRecordId, $listPrice);
	}

	/**
	 * Function that deletes PriceBooks related records information
	 * @param int $sourceRecordId - PriceBook Id
	 * @param int $relatedRecordId - Related Record Id
	 */
	public function deleteRelation($sourceRecordId, $relatedRecordId)
	{
		$sourceModuleName = $this->getParentModuleModel()->get('name');
		$destinationModuleName = $this->getRelationModuleModel()->get('name');
		if ($sourceModuleName == 'PriceBooks' && ($destinationModuleName == 'Products' || $destinationModuleName == 'Services')) {
			$priceBookModel = \App\Modules\Base\Models\Record::getInstanceById($sourceRecordId, $sourceModuleName);
			$priceBookModel->deleteListPrice($relatedRecordId);
		} else {
			parent::deleteRelation($sourceRecordId, $relatedRecordId);
		}
	}

	/**
	 * Get Pricebooks for products
	 */
	public function getPricebookProducts()
	{
		$queryGenerator = $this->getQueryGenerator();
		$queryGenerator->setCustomColumn('vtiger_pricebookproductrel.listprice');
		$queryGenerator->addJoin(['INNER JOIN', 'vtiger_pricebookproductrel', 'vtiger_products.productid = vtiger_pricebookproductrel.productid']);
		$queryGenerator->addJoin(['INNER JOIN', 'vtiger_pricebook', 'vtiger_pricebook.pricebookid = vtiger_pricebookproductrel.pricebookid']);
		$queryGenerator->addNativeCondition(['vtiger_pricebook.pricebookid' => $this->get('parentRecord')->getId()]);
	}

	/**
	 * Get Pricebooks for services
	 */
	public function getPricebookServices()
	{
		$queryGenerator = $this->getQueryGenerator();
		$queryGenerator->setCustomColumn('vtiger_pricebookproductrel.listprice');
		$queryGenerator->addJoin(['INNER JOIN', 'vtiger_pricebookproductrel', 'vtiger_service.serviceid = vtiger_pricebookproductrel.productid']);
		$queryGenerator->addJoin(['INNER JOIN', 'vtiger_pricebook', 'vtiger_pricebook.pricebookid = vtiger_pricebookproductrel.pricebookid']);
		$queryGenerator->addNativeCondition(['vtiger_pricebook.pricebookid' => $this->get('parentRecord')->getId()]);
	}
}
