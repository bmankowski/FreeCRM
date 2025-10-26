<?php

namespace App\Modules\Vtiger\Views;

/**
 * Variable panel view class
 * @package YetiForce.View
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */

use App\Http\Vtiger_Request;
class VariablePanel extends \App\Runtime\BaseViewController
{

	/**
	 * Checking permissions
	 * @param \App\Http\Vtiger_Request $request
	 * @throws \Exception\AppException
	 * @throws \Exception\NoPermittedToRecord
	 */
	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$recordId = $request->get('record');
		$currentUserPrivilegesModel = \App\Modules\Users\Models\Privileges::getCurrentUserPrivilegesModel();
		if (!$currentUserPrivilegesModel->hasModulePermission($moduleName) || !\App\Privilege::isPermitted($moduleName, 'CreateView')) {
			throw new \Exception\NoPermitted('LBL_PERMISSION_DENIED');
		}
		if ($recordId && !\App\Privilege::isPermitted($moduleName, 'EditView', $recordId)) {
			throw new \Exception\NoPermittedToRecord('LBL_NO_PERMISSIONS_FOR_THE_RECORD');
		}
		return true;
	}

	/**
	 * Process function
	 * @param \App\Http\Vtiger_Request $request
	 */
	public function process(\App\Http\Vtiger_Request $request)
	{
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('SELECTED_MODULE', $request->get('selectedModule'));
		$viewer->assign('PARSER_TYPE', $request->get('type'));
		$viewer->assign('GRAY', true);
		$viewer->view('VariablePanel.tpl', $moduleName);
	}
}
