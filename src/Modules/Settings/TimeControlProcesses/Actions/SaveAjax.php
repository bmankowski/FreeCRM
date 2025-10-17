<?php

namespace FreeCRM\Modules\Settings\TimeControlProcesses\Actions;
use FreeCRM\Modules\Settings\TimeControlProcessesModels\Module;


/* +***********************************************************************************************************************************
 * The contents of this file are subject to the YetiForce Public License Version 1.1 (the "License"); you may not use this file except
 * in compliance with the License.
 * Software distributed under the License is distributed on an "AS IS" basis, WITHOUT WARRANTY OF ANY KIND, either express or implied.
 * See the License for the specific language governing rights and limitations under the License.
 * The Original Code is YetiForce.
 * The Initial Developer of the Original Code is YetiForce. Portions created by YetiForce are Copyright (C) www.yetiforce.com. 
 * All Rights Reserved.
 * *********************************************************************************************************************************** */

class SaveAjax extends \FreeCRM\Modules\Settings\Vtiger\Views\IndexAjax
{

	public function process(\FreeCRM\Http\Vtiger_Request $request)
	{
		$params = $request->get('param');
		$moduleModel = \FreeCRM\Modules\Settings\TimeControlProcesses\Models\Module::getCleanInstance();
		$response = new \FreeCRM\Http\Vtiger_Response();
		$response->setResult(array(
			'success' => $moduleModel->setConfig($params),
			'message' => \FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_SAVE_CONFIG', $request->getModule(false))
		));
		$response->emit();
	}
}
