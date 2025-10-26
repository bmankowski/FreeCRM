<?php

namespace App\Modules\Settings\Password\Actions;
use App\Modules\Settings\PasswordModels\Record;


/* +***********************************************************************************************************************************
 * The contents of this file are subject to the YetiForce Public License Version 1.1 (the "License"); you may not use this file except
 * in compliance with the License.
 * Software distributed under the License is distributed on an "AS IS" basis, WITHOUT WARRANTY OF ANY KIND, either express or implied.
 * See the License for the specific language governing rights and limitations under the License.
 * The Original Code is YetiForce.
 * The Initial Developer of the Original Code is YetiForce. Portions created by YetiForce are Copyright (C) www.yetiforce.com. 
 * All Rights Reserved.
 * *********************************************************************************************************************************** */

class Save extends \App\Modules\Settings\Base\Actions\Index
{

	public function process(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule(false);
		$type = $request->get('type');
		$vale = $request->get('vale');
		if (\App\Modules\Settings\Password\Models\Record::validation($type, $vale)) {
			\App\Modules\Settings\Password\Models\Record::setPassDetail($type, $vale);
			$resp = \App\Runtime\Vtiger_Language_Handler::translate('LBL_SAVE_OK', $moduleName);
		} else {
			$resp = \App\Runtime\Vtiger_Language_Handler::translate('LBL_ERROR', $moduleName);
		}
		$response = new \App\Http\Vtiger_Response();
		$response->setResult($resp);
		$response->emit();
	}
}
