<?php

namespace App\Modules\Base\Views;

/**
 * Variable panel view class
 * @package YetiForce.View
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */

use App\Http\Vtiger_Request;
class VariablePanel extends \App\Base\Controllers\BaseViewController
{

	/**
	 * Checking permissions
	 * @param \App\Http\Vtiger_Request $request
	 * @throws \App\Exceptions\AppException
	 * @throws \App\Exceptions\NoPermittedToRecord
	 */
	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$recordId = $request->get('record');
		$currentUserPrivilegesModel = \App\Modules\Users\Models\Privileges::getCurrentUserPrivilegesModel();
		if (!$currentUserPrivilegesModel->hasModulePermission($moduleName) || !\App\Security\Privilege::isPermitted($moduleName, 'CreateView')) {
			throw new \App\Exceptions\NoPermitted('LBL_PERMISSION_DENIED');
		}
		if ($recordId && !\App\Security\Privilege::isPermitted($moduleName, 'EditView', $recordId)) {
			throw new \App\Exceptions\NoPermittedToRecord('LBL_NO_PERMISSIONS_FOR_THE_RECORD');
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
