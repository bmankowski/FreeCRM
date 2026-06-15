<?php

namespace App\Modules\IStorages\Models;

/**
 * RelationListView Model Class
 * @package YetiForce.Model
 * @license licenses/License.html
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 */
class RelationListView extends \App\Runtime\BaseModel
{

	protected $addRelatedFieldToEntries = [
		'Products' => ['qtyproductinstock' => 'qtyproductinstock'],
		'Calendar' => ['visibility' => 'visibility'],
		'PriceBooks' => ['unit_price' => 'unit_price', 'listprice' => 'listprice', 'currency_id' => 'currency_id'],
		'Documents' => ['location_type' => 'location_type', 'active' => 'active']
	];

	public function getHeaders()
	{
		$headerFields = parent::getHeaders();
		if ($this->getRelationModel()->get('modulename') == 'Products' && $this->getRelationModel()->get('name') == 'getManyToMany') {
			$qtyInStock = new \App\Modules\Base\Models\Field();
			$qtyInStock->setModule(\App\Modules\Base\Models\Module::getInstance('Products'));
			$qtyInStock->set('name', 'qtyproductinstock');
			$qtyInStock->set('column', 'qtyproductinstock');
			$qtyInStock->set('label', 'FL_QTY_IN_STOCK');
			$qtyInStock->set('fieldDataType', 'double');
			$qtyInStock->set('fromOutsideList', true);
			$headerFields['qtyproductinstock'] = $qtyInStock;
		}
		return $headerFields;
	}
}
