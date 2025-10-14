<?php

namespace FreeCRM\Modules\Settings\Leads\Actions;


/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

use FreeCRM\Modules\Settings\Leads\Models\Mapping as Settings_Leads_Mapping_Model;
class MappingDelete extends \FreeCRM\Modules\Settings\Vtiger\Actions\Index
{

	public function process(\FreeCRM\Http\Vtiger_Request $request)
	{
		$recordId = $request->get('mappingId');
		$qualifiedModuleName = $request->getModule(false);

		$response = new \FreeCRM\Http\Vtiger_Response();
		if ($recordId) {
			Settings_Leads_Mapping_Model::deleteMapping(array($recordId));
			$response->setResult(array(vtranslate('LBL_DELETED_SUCCESSFULLY', $qualifiedModuleName)));
		} else {
			$response->setError(vtranslate('LBL_INVALID_MAPPING', $qualifiedModuleName));
		}
		$response->emit();
	}
}
