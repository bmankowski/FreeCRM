<?php

namespace FreeCRM\Modules\OSSMailView\Actions;

/**
 * Relation action class
 * @package YetiForce.Action
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */
class Relation extends \FreeCRM\Runtime\Vtiger_Action_Controller
{

	public function checkPermission(\FreeCRM\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$currentUserPriviligesModel = \FreeCRM\Modules\Users\Models\Privileges::getCurrentUserPrivilegesModel();
		if (!$currentUserPriviligesModel->hasModulePermission($moduleName)) {
			throw new \Exception\NoPermitted('LBL_PERMISSION_DENIED');
		}
		if (!\App\Privilege::isPermitted($moduleName, 'ReloadRelationRecord')) {
			throw new \Exception\NoPermitted('LBL_PERMISSION_DENIED');
		}
	}

	public function process(\FreeCRM\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$recordModel = \FreeCRM\Modules\Vtiger\Models\Record::getCleanInstance($moduleName);
		$recordModel->setReloadRelationRecord($request->get('moduleName'), $request->get('record'));

		$response = new \FreeCRM\Http\Vtiger_Response();
		$response->setResult(vtranslate('LBL_SET_RELOAD_RELATIONS', $moduleName));
		$response->emit();
	}
}
