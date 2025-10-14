<?php

namespace FreeCRM\Modules\Settings\SharingAccess\Actions;
use FreeCRM\Modules\Settings\Vtiger\Models\Tracker;


/* +***********************************************************************************************************************************
 * The contents of this file are subject to the YetiForce Public License Version 1.1 (the "License"); you may not use this file except
 * in compliance with the License.
 * Software distributed under the License is distributed on an "AS IS" basis, WITHOUT WARRANTY OF ANY KIND, either express or implied.
 * See the License for the specific language governing rights and limitations under the License.
 * The Original Code is YetiForce.
 * The Initial Developer of the Original Code is YetiForce. Portions created by YetiForce are Copyright (C) www.yetiforce.com. 
 * All Rights Reserved.
 * *********************************************************************************************************************************** */

use FreeCRM\Modules\Settings\SharingAccess\Models\Module as Settings_SharingAccess_Module_Model;

use FreeCRM\Modules\Settings\SharingAccess\Models\Rule as Settings_SharingAccess_Rule_Model;

use FreeCRM\Http\Vtiger_Response;
use FreeCRM\Http\Vtiger_Request;
Class Settings_SharingAccess_IndexAjax_Action extends \FreeCRM\Modules\Settings\Vtiger\Actions\Save
{

	public function __construct()
	{
		\FreeCRM\Modules\Settings\Vtiger\Models\Tracker::lockTracking();
		parent::__construct();
		$this->exposeMethod('saveRule');
		$this->exposeMethod('deleteRule');
	}

	public function process(Vtiger_Request $request)
	{
		$mode = $request->get('mode');
		if (!empty($mode)) {
			$this->invokeExposedMethod($mode, $request);
			return;
		}
	}

	public function saveRule(Vtiger_Request $request)
	{
		\FreeCRM\Modules\Settings\Vtiger\Models\Tracker::lockTracking(false);
		\FreeCRM\Modules\Settings\Vtiger\Models\Tracker::addBasic('save');
		$forModule = $request->get('for_module');
		$ruleId = $request->get('record');

		\App\Privilege::setUpdater($forModule);
		$moduleModel = Settings_SharingAccess_Module_Model::getInstance($forModule);
		if (empty($ruleId)) {
			$ruleModel = new Settings_SharingAccess_Rule_Model();
			$ruleModel->setModuleFromInstance($moduleModel);
		} else {
			$ruleModel = Settings_SharingAccess_Rule_Model::getInstance($moduleModel, $ruleId);
		}

		$prevValues['permission'] = $ruleModel->getPermission();
		$newValues['permission'] = $request->get('permission');

		\FreeCRM\Modules\Settings\Vtiger\Models\Tracker::addDetail($prevValues, $newValues);

		$ruleModel->set('source_id', $request->get('source_id'));
		$ruleModel->set('target_id', $request->get('target_id'));
		$ruleModel->set('permission', $request->get('permission'));

		$response = new \FreeCRM\Http\Vtiger_Response();
		$response->setEmitType(\FreeCRM\Http\Vtiger_Response::$EMIT_JSON);
		try {
			$ruleModel->save();
		} catch (\Exception\AppException $e) {
			$response->setError('Saving Sharing Access Rule failed');
		}
		$response->emit();
	}

	public function deleteRule(Vtiger_Request $request)
	{
		\FreeCRM\Modules\Settings\Vtiger\Models\Tracker::lockTracking(false);
		\FreeCRM\Modules\Settings\Vtiger\Models\Tracker::addBasic('delete');
		$forModule = $request->get('for_module');
		$ruleId = $request->get('record');

		\App\Privilege::setUpdater(vtlib\Functions::getModuleName($forModule));
		$moduleModel = Settings_SharingAccess_Module_Model::getInstance($forModule);
		$ruleModel = Settings_SharingAccess_Rule_Model::getInstance($moduleModel, $ruleId);

		$response = new \FreeCRM\Http\Vtiger_Response();
		$response->setEmitType(\FreeCRM\Http\Vtiger_Response::$EMIT_JSON);
		try {
			$ruleModel->delete();
		} catch (\Exception\AppException $e) {
			$response->setError('Deleting Sharing Access Rule failed');
		}
		$response->emit();
	}

	public function validateRequest(Vtiger_Request $request)
	{
		$request->validateWriteAccess();
	}
}
