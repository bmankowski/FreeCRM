<?php

namespace App\Modules\Products\Models;
use App\Modules\Settings\SalesProcessesModels\Module;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

class ListView extends \App\Modules\Base\Models\ListView
{

	/**
	 * Set list view order by
	 */
	public function loadListViewOrderBy()
	{
		//List view will be displayed on recently created/modified records
		if (empty($this->getForSql('orderby')) && empty($this->getForSql('sortorder')) && $this->getModule()->get('name') != "Users") {
			$this->set('orderby', 'modifiedtime');
			$this->set('sortorder', 'DESC');
		}
		parent::loadListViewOrderBy();
	}

	/**
	 * Function to get the list view entries
	 * @param \App\Modules\Base\Models\Paging $pagingModel
	 * @return array - Associative array of record id mapped to \App\Modules\Base\Models\Record instance.
	 */
	public function getListViewEntries(\App\Modules\Base\Models\Paging $pagingModel, $searchResult = false)
	{
		$moduleModel = $this->getModule();
		$moduleName = $moduleModel->get('name');
		$this->loadListViewCondition();
		$this->loadListViewOrderBy();
		$queryGenerator = $this->get('query_generator');
		$query = $queryGenerator->createQuery();
		// Limit the choice of products/services only to the ones related to currently selected Opportunity - last step.
		if (\App\Modules\Settings\SalesProcesses\Models\Module::checkRelatedToPotentialsLimit($this->get('src_module'))) {
			$salesProcessId = $this->get('salesprocessid');
			if (empty($salesProcessId)) {
				$salesProcessId = -1;
			}
			if ($moduleName == 'Products') {
				$query->innerJoin('vtiger_crmentityrel', 'vtiger_crmentityrel.relcrmid = vtiger_products.productid OR vtiger_crmentityrel.crmid = vtiger_products.productid');
			} elseif ($moduleName == 'Services') {
				$query->innerJoin('vtiger_crmentityrel', 'vtiger_crmentityrel.relcrmid = vtiger_service.serviceid OR vtiger_crmentityrel.crmid = vtiger_service.serviceid');
			}
			if (in_array($moduleName, ['Products', 'Services'])) {
				$query->andWhere(['or',
					['vtiger_crmentityrel.crmid' => $salesProcessId, 'module' => 'SSalesProcesses'],
					['vtiger_crmentityrel.relcrmid' => $salesProcessId, 'relmodule' => 'SSalesProcesses']
				]);
			}
		}
		if ($this->get('subProductsPopup')) {
			$this->addSubProductsQuery($query);
		}
		$sourceModule = $this->get('src_module');
		$sourceField = $this->get('src_field');
		$pageLimit = $pagingModel->getPageLimit();
		//For Products popup in Price Book Related list
		if ($sourceModule !== 'PriceBooks' && $sourceField !== 'priceBookRelatedList') {
			$query->limit($pageLimit + 1)->offset($pagingModel->getStartIndex());
		}
		$rows = $query->all();
		$count = count($rows);
		$pagingModel->calculatePageRange($count);
		if ($count > $pageLimit && $sourceModule !== 'PriceBooks' && $sourceField !== 'priceBookRelatedList') {
			array_pop($rows);
			$pagingModel->set('nextPageExists', true);
		} else {
			$pagingModel->set('nextPageExists', false);
		}
		$listViewRecordModels = [];
		foreach ($rows as &$row) {
			$recordModel = $moduleModel->getRecordFromArray($row);
			$recordModel->colorList = \App\Modules\Settings\DataAccess\Models\Module::executeColorListHandlers($moduleName, $row['id'], $recordModel);
			$listViewRecordModels[$row['id']] = $recordModel;
		}
		unset($rows);
		return $listViewRecordModels;
	}

	public function addSubProductsQuery(\App\Db\Query $listQuery)
	{
		$listQuery->leftJoin('vtiger_seproductsrel', 'vtiger_seproductsrel.crmid = vtiger_products.productid AND vtiger_seproductsrel.setype=:products', [':products' => 'Products']);
		$listQuery->andWhere(['vtiger_seproductsrel.productid' => $this->get('productId')]);
	}

	public function getSubProducts($subProductId)
	{
		$flag = false;
		if (!empty($subProductId)) {
			$db = \App\Database\PearDatabase::getInstance();
			$result = $db->pquery("SELECT vtiger_seproductsrel.crmid from vtiger_seproductsrel INNER JOIN
                vtiger_crmentity ON vtiger_seproductsrel.crmid = vtiger_crmentity.crmid 
					AND vtiger_crmentity.deleted = 0 && vtiger_seproductsrel.setype=? 
				WHERE vtiger_seproductsrel.productid=?", array($this->getModule()->get('name'), $subProductId));
			if ($db->num_rows($result) > 0) {
				$flag = true;
			}
		}
		return $flag;
	}

	/**
	 * Function to get the list view entries
	 * @param \App\Modules\Base\Models\Paging $pagingModel
	 * @return array - Associative array of record id mapped to \App\Modules\Base\Models\Record instance.
	 */
	public function getListViewCount()
	{
		$query = $this->get('query_generator')->createQuery();
		if ($this->get('subProductsPopup')) {
			$this->addSubProductsQuery($query);
		}
		return $query->count();
	}
}
