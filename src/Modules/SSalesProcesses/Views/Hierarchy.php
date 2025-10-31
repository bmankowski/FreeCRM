<?php

namespace App\Modules\SSalesProcesses\Views;

/**
 * Class to show hierarchy 
 * @package YetiForce.View
 * @license licenses/License.html
 * @author Tomasz Kur <t.kur@yetiforce.com>
 */

use App\Http\Vtiger_Request;
class Hierarchy extends \App\Base\Controllers\BaseViewController
{

	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		if (!\App\Privilege::isPermitted($request->getModule(), 'DetailView', $request->get('record'))) {
			throw new \App\Exceptions\NoPermitted('LBL_PERMISSION_DENIED');
		}
	}

	public function preProcess(\App\Http\Vtiger_Request $request, $display = true)
	{
		
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		$recordId = $request->get('record');
		$currentUser = $request->getUser();

		$recordModel = \App\Modules\Base\Models\Record::getInstanceById($recordId, $moduleName);
		$hierarchy = $recordModel->getHierarchy($currentUser);

		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('HIERARCHY', $hierarchy);
		$viewer->view('Hierarchy.tpl', $moduleName);
	}

	public function postProcess(\App\Http\Vtiger_Request $request)
	{
		
	}
}
