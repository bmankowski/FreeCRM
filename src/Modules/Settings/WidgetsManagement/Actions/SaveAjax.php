<?php

namespace FreeCRM\Modules\Settings\WidgetsManagement\Actions;
use FreeCRM\Modules\Settings\WidgetsManagement\Models\Module as Settings_WidgetsManagement_Module_Model;



/**
 * @package YetiForce.Action
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 */
class SaveAjax extends \FreeCRM\Modules\Settings\Vtiger\Views\IndexAjax
{

	public function checkPermission(\FreeCRM\Http\Vtiger_Request $request)
	{
		$currentUserModel = \FreeCRM\Modules\Users\Models\Record::getCurrentUserModel();
		$mode = $request->get('mode');
		if ($mode == 'delete' && !$currentUserModel->isAdminUser()) {
			throw new \Exception\AppException(vtranslate('LBL_PERMISSION_DENIED', 'Vtiger'));
		}
		$sourceModule = $request->get('sourceModule');
		$currentUserPriviligesModel = \FreeCRM\Modules\Users\Models\Privileges::getCurrentUserPrivilegesModel();
		if (!$currentUserPriviligesModel->hasModuleActionPermission($sourceModule, 'Save')) {
			throw new \Exception\AppException(vtranslate('LBL_PERMISSION_DENIED', 'Vtiger'));
		}
	}

	public function __construct()
	{
		parent::__construct();
		$this->exposeMethod('save');
		$this->exposeMethod('delete');
	}

	public function save(\FreeCRM\Http\Vtiger_Request $request)
	{
		$data = $request->get('form');
		$moduleName = $request->get('sourceModule');
		$addToUser = $request->get('addToUser');
		if (!is_array($data) || !$data) {
			$result = array('success' => false, 'message' => vtranslate('LBL_INVALID_DATA', $moduleName));
		} else {
			if (!$data['action'])
				$data['action'] = 'saveDetails';
			$action = $data['action'];
			$widgetsManagementModel = new \FreeCRM\Modules\Settings\WidgetsManagement\Models\Module();
			$result = $widgetsManagementModel->$action($data, $moduleName, $addToUser);
		}
		$response = new \FreeCRM\Http\Vtiger_Response();
		$response->setResult($result);
		$response->emit();
	}

	public function delete(\FreeCRM\Http\Vtiger_Request $request)
	{
		$data = $request->get('form');
		$moduleName = $request->get('sourceModule');
		if (!is_array($data) || !$data) {
			$result = array('success' => false, 'message' => vtranslate('LBL_INVALID_DATA', $moduleName));
		} else {
			$action = $data['action'];
			if (!$action){
				$action = 'removeWidget';
			}
			$widgetsManagementModel = new \FreeCRM\Modules\Settings\WidgetsManagement\Models\Module();
			$result = $widgetsManagementModel->$action($data);
		}
		$response = new \FreeCRM\Http\Vtiger_Response();
		$response->setResult($result);
		$response->emit();
	}
}
