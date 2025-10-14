<?php

namespace FreeCRM\Modules\Settings\Leads\Actions;


/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * *********************************************************************************** */

use FreeCRM\Modules\Settings\Leads\Models\Mapping as Settings_Leads_Mapping_Model;
class MappingSave extends \FreeCRM\Modules\Settings\Vtiger\Actions\Index
{

	public function process(\FreeCRM\Http\Vtiger_Request $request)
	{
		$mapping = $request->get('mapping');
		$csrfKey = $GLOBALS['csrf']['input-name'];
		if (array_key_exists($csrfKey, $mapping)) {
			unset($mapping[$csrfKey]);
		}
		$mappingModel = Settings_Leads_Mapping_Model::getCleanInstance();

		$response = new \FreeCRM\Http\Vtiger_Response();
		if ($mapping) {
			$mappingModel->save($mapping);
			$result = array('status' => true);
		} else {
			$result['status'] = false;
		}
		$response->setResult($result);
		return $response->emit();
	}

	public function validateRequest(\FreeCRM\Http\Vtiger_Request $request)
	{
		$request->validateWriteAccess();
	}
}
