<?php

/**
 * Action to mass upload files
 * @package YetiForce.Modal
 * @license licenses/License.html
 * @author Tomasz Kur <t.kur@yetiforce.com>
 */

use FreeCRM\Http\Vtiger_Request;
class Documents_MassAddDocuments_View extends Vtiger_BasicModal_View
{

	/**
	 * Function to check permission
	 * @param Vtiger_Request $request
	 * @throws \Exception\NoPermitted
	 */
	public function checkPermission(\FreeCRM\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();

		if (!Users_Privileges_Model::isPermitted($moduleName, 'CreateView')) {
			throw new \Exception\NoPermitted('LBL_PERMISSION_DENIED');
		}
	}

	/**
	 * Process
	 * @param Vtiger_Request $request
	 */
	public function process(\FreeCRM\Http\Vtiger_Request $request)
	{
		parent::preProcess($request);
		$moduleName = $request->getModule();
		$viewer = $this->getViewer($request);
		$viewer->assign('MODULE', $moduleName);
		$viewer->view('MassAddDocuments.tpl', $moduleName);
		parent::postProcess($request);
	}
}
