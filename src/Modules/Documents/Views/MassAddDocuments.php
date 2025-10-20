<?php

namespace App\Modules\Documents\Views;

/**
 * Action to mass upload files
 * @package YetiForce.Modal
 * @license licenses/License.html
 * @author Tomasz Kur <t.kur@yetiforce.com>
 */

use App\Http\Vtiger_Request;
class MassAddDocuments  extends \App\Modules\Vtiger\Views\Index
{

	/**
	 * Function to check permission
	 * @param Vtiger_Request $request
	 * @throws \Exception\NoPermitted
	 */
	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();

		if (!\App\Modules\Users\Models\Privileges::isPermitted($moduleName, 'CreateView')) {
			throw new \Exception\NoPermitted('LBL_PERMISSION_DENIED');
		}
	}

	/**
	 * Process
	 * @param Vtiger_Request $request
	 */
	public function process(\App\Http\Vtiger_Request $request)
	{
		parent::preProcess($request);
		$moduleName = $request->getModule();
		$viewer = $this->getViewer($request);
		$viewer->assign('MODULE', $moduleName);
		$viewer->view('MassAddDocuments.tpl', $moduleName);
		parent::postProcess($request);
	}
}
