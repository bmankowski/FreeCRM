<?php

namespace App\Modules\OSSMailView\Actions;

/**
 * Relation action class
 * @package YetiForce.Action
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */
class Relation extends \App\Runtime\BaseActionController
{

	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$currentUserPriviligesModel = \App\Modules\Users\Models\Privileges::getCurrentUserPrivilegesModel();
		if (!$currentUserPriviligesModel->hasModulePermission($moduleName)) {
			throw new \App\Exceptions\NoPermitted('LBL_PERMISSION_DENIED');
		}
		if (!\App\Privilege::isPermitted($moduleName, 'ReloadRelationRecord')) {
			throw new \App\Exceptions\NoPermitted('LBL_PERMISSION_DENIED');
		}
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$recordModel = \App\Modules\Vtiger\Models\Record::getCleanInstance($moduleName);
		$recordModel->setReloadRelationRecord($request->get('moduleName'), $request->get('record'));

		$response = new \App\Http\Vtiger_Response();
		$response->setResult(\App\Runtime\Vtiger_Language_Handler::translate('LBL_SET_RELOAD_RELATIONS', $moduleName));
		$response->emit();
	}
}
