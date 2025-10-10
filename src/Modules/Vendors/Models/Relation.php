<?php

namespace FreeCRM\Modules\Vendors\Models;

/**
 * Relation Model
 * @package YetiForce.Model
 * @license licenses/License.html
 * @author Tomasz Kur <t.kur@yetiforce.com>
 */
class Relation extends \FreeCRM\Modules\Vtiger\Models\Relation
{

	/**
	 * Get products
	 */
	public function getProducts()
	{
		$queryGenerator = $this->getQueryGenerator();
		$queryGenerator->addJoin(['INNER JOIN', 'vtiger_vendor', 'vtiger_vendor.vendorid = vtiger_products.vendor_id']);
		$queryGenerator->addNativeCondition(['vtiger_vendor.vendorid' => $this->get('parentRecord')->getId()]);
	}
}
