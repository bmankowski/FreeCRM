<?php

namespace App\Modules\Base\Actions;

/**
 * RemoveWidgetFromList Class
 * @package YetiForce.Action
 * @license licenses/License.html
 * @author Adrian Koń  <a.kon@yetiforce.com>
 */
use App\Http\Vtiger_Request;

class RemoveWidgetFromList  extends \App\Modules\Base\Views\Index
{

	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		$userPrivilegesModel = \App\Modules\Users\Models\Privileges::getCurrentUserPrivilegesModel();
		$permission = $userPrivilegesModel->hasModulePermission($request->getModule());
		if (!$permission) {
			throw new \App\Exceptions\NoPermitted('LBL_PERMISSION_DENIED');
		}
		return true;
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$currentUser = $request->getUser();
		if ($request->has('id')) {
			$id = $request->get('id');
			$widget = \App\Modules\Base\Models\Widget::getInstanceWithWidgetId($id, $currentUser->getId());
			if (!$widget->isDefault()) {
				\App\Modules\Base\Models\Widget::removeWidgetFromList($id);
			}
		}

		$response = new \App\Http\Vtiger_Response();
		$response->setResult(true);
		$response->emit();
	}
}
