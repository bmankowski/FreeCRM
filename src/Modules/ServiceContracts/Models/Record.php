<?php

namespace App\Modules\ServiceContracts\Models;

/**
 * Service contracts record model Class
 * @package YetiForce.Model
 * @license licenses/License.html
 * @author Tomasz Kur <t.kur@yetiforce.com>
 */
class Record extends \App\Modules\Vtiger\Models\Record
{

	/**
	 * Function to save record
	 */
	public function saveToDb()
	{
		parent::saveToDb();
		$forModule = \App\Http\AppRequest::get('return_module');
		$forCrmid = \App\Http\AppRequest::get('return_id');
		if (\App\Http\AppRequest::get('return_action') && $forModule && $forCrmid && $forModule === 'HelpDesk') {
			\App\CRMEntity::getInstance($forModule)->save_related_module($forModule, $forCrmid, \App\Http\AppRequest::get('module'), $this->getId());
		}
	}
}
