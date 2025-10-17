<?php

namespace App\Modules\Settings\Leads\Actions;


/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

use App\Modules\Settings\Leads\Models\Mapping as Settings_Leads_Mapping_Model;
class MappingDelete extends \App\Modules\Settings\Vtiger\Actions\Index
{

	public function process(\App\Http\Vtiger_Request $request)
	{
		$recordId = $request->get('mappingId');
		$qualifiedModuleName = $request->getModule(false);

		$response = new \App\Http\Vtiger_Response();
		if ($recordId) {
			Settings_Leads_Mapping_Model::deleteMapping(array($recordId));
			$response->setResult(array(\App\Runtime\Vtiger_Language_Handler::translate('LBL_DELETED_SUCCESSFULLY', $qualifiedModuleName)));
		} else {
			$response->setError(\App\Runtime\Vtiger_Language_Handler::translate('LBL_INVALID_MAPPING', $qualifiedModuleName));
		}
		$response->emit();
	}
}
