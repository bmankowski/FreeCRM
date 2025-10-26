<?php

namespace App\Modules\OSSEmployees\Models;

/**
 * Relation Model
 * @package YetiForce.Model
 * @license licenses/License.html
 * @author Tomasz Kur <t.kur@yetiforce.com>
 */
class Relation extends \App\Modules\Base\Models\Relation
{

	/**
	 * Get time control
	 */
	public function getOsstimecontrol()
	{
		$this->getQueryGenerator()->addNativeCondition(['vtiger_crmentity.smownerid' => $this->get('parentRecord')->get('assigned_user_id')]);
	}
}
