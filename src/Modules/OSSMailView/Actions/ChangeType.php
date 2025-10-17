<?php

namespace App\Modules\OSSMailView\Actions;

/**
 * Change type action class
 * @package YetiForce.Action
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */
class ChangeType extends \App\Runtime\Vtiger_Action_Controller
{

	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		$currentUserPriviligesModel = \App\Modules\Users\Models\Privileges::getCurrentUserPrivilegesModel();
		if (!$currentUserPriviligesModel->hasModulePermission($request->getModule())) {
			throw new \Exception\NoPermitted('LBL_PERMISSION_DENIED');
		}
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$recordModel = \App\Modules\Vtiger\Models\Record::getCleanInstance($moduleName);
		$selectedIds = $request->get('data');
		$mail_type = $request->get('mail_type');
		if ($selectedIds == 'all') {
			$recordModel->ChangeTypeAllRecords($mail_type);
		} else {
			$recordModel->ChangeTypeSelectedRecords($selectedIds, $mail_type);
		}
		$response = new \App\Http\Vtiger_Response();
		$response->setResult(\App\Runtime\Vtiger_Language_Handler::translate('LBL_ChangeTypeOK', $moduleName));
		$response->emit();
	}
}
