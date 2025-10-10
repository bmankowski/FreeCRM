<?php

namespace FreeCRM\Modules\SSalesProcesses\Views;

/**
 * Class to show hierarchy 
 * @package YetiForce.View
 * @license licenses/License.html
 * @author Tomasz Kur <t.kur@yetiforce.com>
 */

use FreeCRM\Http\Vtiger_Request;
class Hierarchy extends \FreeCRM\Runtime\Vtiger_View_Controller
{

	public function checkPermission(\FreeCRM\Http\Vtiger_Request $request)
	{
		if (!\App\Privilege::isPermitted($request->getModule(), 'DetailView', $request->get('record'))) {
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

		$recordModel = \FreeCRM\Modules\Vtiger\Models\Record::getInstanceById($recordId, $moduleName);
		$hierarchy = $recordModel->getHierarchy();

		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('HIERARCHY', $hierarchy);
		$viewer->view('Hierarchy.tpl', $moduleName);
	}

	public function postProcess(\FreeCRM\Http\Vtiger_Request $request)
	{
		
	}
}
