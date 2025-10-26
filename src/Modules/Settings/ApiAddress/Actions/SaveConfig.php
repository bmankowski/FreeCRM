<?php

namespace App\Modules\Settings\ApiAddress\Actions;


/* +***********************************************************************************************************************************
 * The contents of this file are subject to the YetiForce Public License Version 1.1 (the "License"); you may not use this file except
 * in compliance with the License.
 * Software distributed under the License is distributed on an "AS IS" basis, WITHOUT WARRANTY OF ANY KIND, either express or implied.
 * See the License for the specific language governing rights and limitations under the License.
 * The Original Code is YetiForce.
 * The Initial Developer of the Original Code is YetiForce. Portions created by YetiForce are Copyright (C) www.yetiforce.com. 
 * All Rights Reserved.
 * *********************************************************************************************************************************** */

class SaveConfig extends \App\Modules\Settings\Base\Actions\Basic
{

	public function process(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule(false);
		$elements = $request->get('elements');

		$result = \App\Modules\Settings\ApiAddress\Models\Module::getInstance($moduleName)->setConfig($elements);

		if ($result)
			$result = array('success' => true, 'message' => \App\Runtime\Vtiger_Language_Handler::translate('LBL_SAVE_NOTIFY_OK', $moduleName));
		else
			$result = array('success' => false, 'message' => \App\Runtime\Vtiger_Language_Handler::translate('JS_ERROR', $moduleName));

		$response = new \App\Http\Vtiger_Response();
		$response->setResult($result);
		$response->emit();
	}
}
