<?php

namespace App\Modules\IStorages\Models;

/**
 * Relation Model Class
 * @package YetiForce.Model
 * @license licenses/License.html
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 */
class Relation extends \App\Modules\Base\Models\Relation
{

	/**
	 * Get many to many
	 */
	public function getManyToMany()
	{
		if ($this->getRelationModuleName() === 'Products') {
			$queryGenerator = $this->getQueryGenerator();
			$queryGenerator->setCustomColumn('u_#__istorages_products.qtyinstock qtyproductinstock');
		}
		parent::getManyToMany();
	}
}
