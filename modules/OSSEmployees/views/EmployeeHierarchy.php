<?php
/* {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} */


use FreeCRM\Http\Vtiger_Request;
class OSSEmployees_EmployeeHierarchy_View extends Vtiger_View_Controller
{

	public function checkPermission(\FreeCRM\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$currentUserPriviligesModel = Users_Privileges_Model::getCurrentUserPrivilegesModel();
		if (!$currentUserPriviligesModel->hasModulePermission($moduleName)) {
			throw new \Exception\NoPermitted('LBL_PERMISSION_DENIED');
		}
	}

	public function preProcess(\FreeCRM\Http\Vtiger_Request $request, $display = true)
	{
		
	}

	public function process(\FreeCRM\Http\Vtiger_Request $request)
	{
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		$recordId = $request->get('record');

		$recordModel = Vtiger_Record_Model::getInstanceById($recordId, $moduleName);
		$hierarchy = $recordModel->getEmployeeHierarchy();

		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('EMPLOYEES_HIERARCHY', $hierarchy);
		$viewer->view('EmployeeHierarchy.tpl', $moduleName);
	}

	public function postProcess(\FreeCRM\Http\Vtiger_Request $request)
	{
		
	}
}
