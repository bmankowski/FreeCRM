<?php

namespace App\Modules\Vtiger\Actions;

/**
 * RemoveWidgetFromList Class
 * @package YetiForce.Action
 * @license licenses/License.html
 * @author Adrian Koń  <a.kon@yetiforce.com>
 */
use App\Http\Vtiger_Request;

class RemoveWidgetFromList  extends \App\Modules\Vtiger\Views\Index
{

	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		$userPrivilegesModel = \App\Modules\Users\Models\Privileges::getCurrentUserPrivilegesModel();
		$permission = $userPrivilegesModel->hasModulePermission($request->getModule());
		if (!$permission) {
			throw new \Exception\NoPermitted('LBL_PERMISSION_DENIED');
		}
		return true;
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$currentUser = \App\Modules\Users\Models\Record::getCurrentUserModel();
		if ($request->has('id')) {
			$id = $request->get('id');
			$widget = \App\Modules\Vtiger\Models\Widget::getInstanceWithWidgetId($id, $currentUser->getId());
			if (!$widget->isDefault()) {
				\App\Modules\Vtiger\Models\Widget::removeWidgetFromList($id);
			}
		}

		$response = new \App\Http\Vtiger_Response();
		$response->setResult(true);
		$response->emit();
	}
}
