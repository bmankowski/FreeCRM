<?php

namespace App\Modules\Settings\WidgetsManagement\Actions;



/**
 * @package YetiForce.Action
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 */
class SaveAjax extends \App\Modules\Settings\Base\Views\IndexAjax
{

	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		$currentUserModel = $request->getUser();
		$mode = $request->get('mode');
		if ($mode == 'delete' && !$currentUserModel->isAdminUser()) {
			throw new \App\Exceptions\AppException(\App\Runtime\Vtiger_Language_Handler::translate('LBL_PERMISSION_DENIED', 'Vtiger'));
		}
		$sourceModule = $request->get('sourceModule');
		$currentUserPriviligesModel = \App\Modules\Users\Models\Privileges::getCurrentUserPrivilegesModel();
		if (!$currentUserPriviligesModel->hasModuleActionPermission($sourceModule, 'Save')) {
			throw new \App\Exceptions\AppException(\App\Runtime\Vtiger_Language_Handler::translate('LBL_PERMISSION_DENIED', 'Vtiger'));
		}
	}

	public function __construct()
	{
		parent::__construct();
		$this->exposeMethod('save');
		$this->exposeMethod('delete');
	}

	public function save(\App\Http\Vtiger_Request $request)
	{
		$data = $request->get('form');
		$moduleName = $request->get('sourceModule');
		$addToUser = $request->get('addToUser');
		if (!is_array($data) || !$data) {
			$result = array('success' => false, 'message' => \App\Runtime\Vtiger_Language_Handler::translate('LBL_INVALID_DATA', $moduleName));
		} else {
			if (!$data['action'])
				$data['action'] = 'saveDetails';
			$action = $data['action'];
			$widgetsManagementModel = new \App\Modules\Settings\WidgetsManagement\Models\Module();
			$result = $widgetsManagementModel->$action($data, $moduleName, $addToUser);
		}
		$response = new \App\Http\Vtiger_Response();
		$response->setResult($result);
		$response->emit();
	}

	public function delete(\App\Http\Vtiger_Request $request)
	{
		$data = $request->get('form');
		$moduleName = $request->get('sourceModule');
		if (!is_array($data) || !$data) {
			$result = array('success' => false, 'message' => \App\Runtime\Vtiger_Language_Handler::translate('LBL_INVALID_DATA', $moduleName));
		} else {
			$action = $data['action'];
			if (!$action){
				$action = 'removeWidget';
			}
			$widgetsManagementModel = new \App\Modules\Settings\WidgetsManagement\Models\Module();
			$result = $widgetsManagementModel->$action($data);
		}
		$response = new \App\Http\Vtiger_Response();
		$response->setResult($result);
		$response->emit();
	}
}
