<?php

namespace App\Modules\Settings\ModTracker\Actions;
use App\Modules\Settings\ModTrackerModels\Module;


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

	public function __construct()
	{
		parent::__construct();
		$this->exposeMethod('changeActiveStatus');
	}

	public function changeActiveStatus(\App\Http\Vtiger_Request $request)
	{
		$id = $request->get('id');
		$status = $request->get('status');
		$moduleModel = new \App\Modules\Settings\ModTracker\Models\Module();
		$moduleModel->changeActiveStatus($id, $status == 'true' ? 1 : 0 );

		$response = new \App\Http\Vtiger_Response();
		if ($status == 'true') {
			$response->setResult(array('success' => true, 'message' => \App\Runtime\Vtiger_Language_Handler::translate('LBL_TRACK_CHANGES_ENABLED', $request->getModule(false))));
		} else {
			$response->setResult(array('success' => true, 'message' => \App\Runtime\Vtiger_Language_Handler::translate('LBL_TRACK_CHANGES_DISABLE', $request->getModule(false))));
		}
		$response->emit();
	}
}
