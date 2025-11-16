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

class SaveAjax extends \App\Modules\Settings\Base\Actions\Save
{

	public function process(\App\Http\Vtiger_Request $request)
	{
		$modulePermissions = $request->get('permissions');
		$modulePermissions[4] = $modulePermissions[6];

		$postValues = [];
		$prevValues = [];
		foreach ($modulePermissions as $tabId => $permission) {
			$moduleModel = \App\Modules\Settings\SharingAccess\Models\Module::getInstance($tabId);
			$permissionOld = $moduleModel->get('permission');
			$moduleModel->set('permission', $permission);
			if ($permissionOld != $permission) {
				$prevValues[$tabId] = $permissionOld;
				$postValues[$tabId] = $moduleModel->get('permission');
				if ($permissionOld == 3 || $moduleModel->get('permission') == 3) {
					\App\Security\Privilege::setUpdater(\App\Utils\ModuleUtils::getModuleName($tabId));
				}
			}
			try {
				$moduleModel->save();
			} catch (\App\Exceptions\AppException $e) {
				
			}
		}
		\App\Modules\Settings\Base\Models\Tracker::addDetail($prevValues, $postValues);
		\App\Modules\Settings\SharingAccess\Models\Module::recalculateSharingRules();

		$response = new \App\Http\Vtiger_Response();
		$response->setEmitType(\App\Http\Vtiger_Response::$EMIT_JSON);
		$response->emit();
	}
}
