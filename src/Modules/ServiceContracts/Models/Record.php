<?php

namespace FreeCRM\Modules\ServiceContracts\Models;

/**
 * Service contracts record model Class
 * @package YetiForce.Model
 * @license licenses/License.html
 * @author Tomasz Kur <t.kur@yetiforce.com>
 */
class Record extends \FreeCRM\Modules\Vtiger\Models\Record
{

	/**
	 * Function to save record
	 */
	public function saveToDb()
	{
		parent::saveToDb();
		$forModule = \FreeCRM\Http\AppRequest::get('return_module');
		$forCrmid = \FreeCRM\Http\AppRequest::get('return_id');
		if (\FreeCRM\Http\AppRequest::get('return_action') && $forModule && $forCrmid && $forModule === 'HelpDesk') {
			\FreeCRM\CRMEntity::getInstance($forModule)->save_related_module($forModule, $forCrmid, \FreeCRM\Http\AppRequest::get('module'), $this->getId());
		}
	}
}
