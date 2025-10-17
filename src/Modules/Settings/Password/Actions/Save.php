<?php

namespace FreeCRM\Modules\Settings\Password\Actions;
use FreeCRM\Modules\Settings\PasswordModels\Record;


/* +***********************************************************************************************************************************
 * The contents of this file are subject to the YetiForce Public License Version 1.1 (the "License"); you may not use this file except
 * in compliance with the License.
 * Software distributed under the License is distributed on an "AS IS" basis, WITHOUT WARRANTY OF ANY KIND, either express or implied.
 * See the License for the specific language governing rights and limitations under the License.
 * The Original Code is YetiForce.
 * The Initial Developer of the Original Code is YetiForce. Portions created by YetiForce are Copyright (C) www.yetiforce.com. 
 * All Rights Reserved.
 * *********************************************************************************************************************************** */

class Save extends \FreeCRM\Modules\Settings\Vtiger\Actions\Index
{

	public function process(\FreeCRM\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule(false);
		$type = $request->get('type');
		$vale = $request->get('vale');
		if (\FreeCRM\Modules\Settings\Password\Models\Record::validation($type, $vale)) {
			\FreeCRM\Modules\Settings\Password\Models\Record::setPassDetail($type, $vale);
			$resp = \FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_SAVE_OK', $moduleName);
		} else {
			$resp = \FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_ERROR', $moduleName);
		}
		$response = new \FreeCRM\Http\Vtiger_Response();
		$response->setResult($resp);
		$response->emit();
	}
}
