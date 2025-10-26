<?php

namespace App\Modules\Settings\SharingAccess\Actions;
use App\Modules\Settings\Base\Models\Tracker;


/* +***********************************************************************************************************************************
 * The contents of this file are subject to the YetiForce Public License Version 1.1 (the "License"); you may not use this file except
 * in compliance with the License.
 * Software distributed under the License is distributed on an "AS IS" basis, WITHOUT WARRANTY OF ANY KIND, either express or implied.
 * See the License for the specific language governing rights and limitations under the License.
 * The Original Code is YetiForce.
 * The Initial Developer of the Original Code is YetiForce. Portions created by YetiForce are Copyright (C) www.yetiforce.com. 
 * All Rights Reserved.
 * *********************************************************************************************************************************** */



use App\Http\Vtiger_Response;
use App\Http\Vtiger_Request;
class Settings_SharingAccess_IndexAjax_Action extends \App\Modules\Settings\Base\Actions\Save
{

	public function __construct()
	{
		\App\Modules\Settings\Base\Models\Tracker::lockTracking();
		parent::__construct();
		$this->exposeMethod('saveRule');
		$this->exposeMethod('deleteRule');
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$mode = $request->get('mode');
		if (!empty($mode)) {
			$this->invokeExposedMethod($mode, $request);
			return;
		}
	}

	public function saveRule(\App\Http\Vtiger_Request $request)
	{
		\App\Modules\Settings\Base\Models\Tracker::lockTracking(false);
		\App\Modules\Settings\Base\Models\Tracker::addBasic('save');
		$forModule = $request->get('for_module');
		$ruleId = $request->get('record');

		\App\Privilege::setUpdater($forModule);
		$moduleModel = \App\Modules\Settings\SharingAccess\Models\Module::getInstance($forModule);
		if (empty($ruleId)) {
			$ruleModel = new \App\Modules\Settings\SharingAccess\Models\Rule();
			$ruleModel->setModuleFromInstance($moduleModel);
		} else {
			$ruleModel = \App\Modules\Settings\SharingAccess\Models\Rule::getInstance($moduleModel, $ruleId);
		}

		$prevValues['permission'] = $ruleModel->getPermission();
		$newValues['permission'] = $request->get('permission');

		\App\Modules\Settings\Base\Models\Tracker::addDetail($prevValues, $newValues);

		$ruleModel->set('source_id', $request->get('source_id'));
		$ruleModel->set('target_id', $request->get('target_id'));
		$ruleModel->set('permission', $request->get('permission'));

		$response = new \App\Http\Vtiger_Response();
		$response->setEmitType(\App\Http\Vtiger_Response::$EMIT_JSON);
		try {
			$ruleModel->save();
		} catch (\App\Exceptions\AppException $e) {
			$response->setError('Saving Sharing Access Rule failed');
		}
		$response->emit();
	}

	public function deleteRule(\App\Http\Vtiger_Request $request)
	{
		\App\Modules\Settings\Base\Models\Tracker::lockTracking(false);
		\App\Modules\Settings\Base\Models\Tracker::addBasic('delete');
		$forModule = $request->get('for_module');
		$ruleId = $request->get('record');

		\App\Privilege::setUpdater(\vtlib\Functions::getModuleName($forModule));
		$moduleModel = \App\Modules\Settings\SharingAccess\Models\Module::getInstance($forModule);
		$ruleModel = \App\Modules\Settings\SharingAccess\Models\Rule::getInstance($moduleModel, $ruleId);

		$response = new \App\Http\Vtiger_Response();
		$response->setEmitType(\App\Http\Vtiger_Response::$EMIT_JSON);
		try {
			$ruleModel->delete();
		} catch (\App\Exceptions\AppException $e) {
			$response->setError('Deleting Sharing Access Rule failed');
		}
		$response->emit();
	}

	public function validateRequest(\App\Http\Vtiger_Request $request)
	{
		$request->validateWriteAccess();
	}
}
