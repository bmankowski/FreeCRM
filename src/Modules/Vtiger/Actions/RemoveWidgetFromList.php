<?php

namespace FreeCRM\Modules\Vtiger\Actions;

/**
 * RemoveWidgetFromList Class
 * @package YetiForce.Action
 * @license licenses/License.html
 * @author Adrian Koń  <a.kon@yetiforce.com>
 */
use FreeCRM\Http\Vtiger_Request;

class RemoveWidgetFromList extends \Vtiger_Index_View
{

	public function checkPermission(\FreeCRM\Http\Vtiger_Request $request)
	{
		$userPrivilegesModel = \FreeCRM\Modules\Users\Models\Privileges::getCurrentUserPrivilegesModel();
		$permission = $userPrivilegesModel->hasModulePermission($request->getModule());
		if (!$permission) {
			throw new \Exception\NoPermitted('LBL_PERMISSION_DENIED');
		}
		return true;
	}

	public function process(\FreeCRM\Http\Vtiger_Request $request)
	{
		$currentUser = \FreeCRM\Modules\Users\Models\Record::getCurrentUserModel();
		if ($request->has('id')) {
			$id = $request->get('id');
			$widget = \FreeCRM\Modules\Vtiger\Models\Widget::getInstanceWithWidgetId($id, $currentUser->getId());
			if (!$widget->isDefault()) {
				\FreeCRM\Modules\Vtiger\Models\Widget::removeWidgetFromList($id);
			}
		}

		$response = new \FreeCRM\Http\Vtiger_Response();
		$response->setResult(true);
		$response->emit();
	}
}
