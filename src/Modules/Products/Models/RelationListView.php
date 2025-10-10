<?php

namespace FreeCRM\Modules\Products\Models;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * *********************************************************************************** */

class RelationListView extends \FreeCRM\Modules\Vtiger\Models\Model
{

	protected $addRelatedFieldToEntries = [
		'IStorages' => ['qtyinstock' => 'qtyinstock'],
		'Calendar' => ['visibility' => 'visibility'],
		'PriceBooks' => ['unit_price' => 'unit_price', 'listprice' => 'listprice', 'currency_id' => 'currency_id'],
		'Documents' => ['filelocationtype' => 'filelocationtype', 'filestatus' => 'filestatus']
	];

	/**
	 * Function extending recordModel object with additional information
	 * @param \FreeCRM\Modules\Vtiger\Models\Record $recordModel
	 */
	public function getEntryExtend(\FreeCRM\Modules\Vtiger\Models\Record $recordModel)
	{
		if ($this->getRelationModel()->getRelationModuleModel()->getName() === 'PriceBooks') {
			$parentId = $this->getParentRecordModel()->getId();
			$parentModuleModel = $this->getParentRecordModel()->getModule();
			$unitPricesList = $parentModuleModel->getPricesForProducts($recordModel->get('currency_id'), [$parentId => $parentId]);
			$recordModel->set('unit_price', $unitPricesList[$parentId]);
		}
	}

	/**
	 * Function to get the links for related list
	 * @return <Array> List of action models <\FreeCRM\Modules\Vtiger\Models\Link>
	 */
	public function getLinks()
	{
		$relationModel = $this->getRelationModel();
		$parentModel = $this->getParentRecordModel();

		$isSubProduct = false;
		if ($parentModel->getModule()->getName() == $relationModel->getRelationModuleModel()->getName()) {
			$isSubProduct = $relationModel->isSubProduct($parentModel->getId());
		}

		if (!$isSubProduct) {
			return parent::getLinks();
		}
	}

	public function getHeaders()
	{
		$headerFields = parent::getHeaders();
		if ($this->getRelationModel()->get('modulename') == 'IStorages' && $this->getRelationModel()->get('name') == 'getManyToMany') {
			$qtyInStockField = new \FreeCRM\Modules\Vtiger\Models\Field();
			$qtyInStockField->setModule(\FreeCRM\Modules\Vtiger\Models\Module::getInstance('IStorages'));
			$qtyInStockField->set('name', 'qtyinstock');
			$qtyInStockField->set('column', 'qtyinstock');
			$qtyInStockField->set('label', 'FL_QTY_IN_STOCK');
			$qtyInStockField->set('fromOutsideList', true);
			$headerFields['qtyinstock'] = $qtyInStockField;
		}
		if ($this->getRelationModel()->getRelationModuleModel()->getName() == 'PriceBooks') {
			//Added to support Unit Price
			$moduleModel = \FreeCRM\Modules\Vtiger\Models\Module::getInstance('PriceBooks');
			$unitPriceField = new \FreeCRM\Modules\Vtiger\Models\Field();
			$unitPriceField->setModule($moduleModel);
			$unitPriceField->set('name', 'unit_price');
			$unitPriceField->set('column', 'unit_price');
			$unitPriceField->set('label', 'Unit Price');
			$unitPriceField->set('fromOutsideList', true);

			$headerFields['unit_price'] = $unitPriceField;

			//Added to support List Price
			$field = new \FreeCRM\Modules\Vtiger\Models\Field();
			$field->setModule($moduleModel);
			$field->set('name', 'listprice');
			$field->set('column', 'listprice');
			$field->set('label', 'List Price');
			$field->set('fromOutsideList', true);

			$headerFields['listprice'] = $field;
		}

		return $headerFields;
	}
}
