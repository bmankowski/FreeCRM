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
	 * @param array $relationParams Optional relation parameters
	 */
	public function saveToDb($relationParams = null)
	{
		parent::saveToDb($relationParams);
		
		if ($relationParams && !empty($relationParams['return_action'])) {
			$forModule = $relationParams['return_module'] ?? null;
			$forCrmid = $relationParams['return_id'] ?? null;
			$currentModule = $relationParams['current_module'] ?? null;
			
			if ($forModule && $forCrmid && $forModule === 'HelpDesk') {
				\App\CRMEntity::getInstance($forModule)->save_related_module(
					$forModule, 
					$forCrmid, 
					$currentModule, 
					$this->getId()
				);
			}
		}
	}
}
