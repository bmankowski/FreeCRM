<?php

namespace App\Modules\Documents\Views;

/**
 * Action to mass upload files
 * @package YetiForce.Modal
 * @license licenses/License.html
 * @author Tomasz Kur <t.kur@yetiforce.com>
 */

use App\Http\Vtiger_Request;

class MassAddDocuments extends \App\Modules\Base\Views\BasicModal
{
	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();

		if (!\App\Modules\Users\Models\Privileges::isPermitted($moduleName, 'CreateView')) {
			throw new \App\Exceptions\NoPermitted('LBL_PERMISSION_DENIED');
		}
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$this->preProcess($request);
		$moduleName = $request->getModule();
		$viewer = $this->getViewer($request);
		$viewer->assign('MODULE', $moduleName);
		$viewer->view('MassAddDocuments.tpl', $moduleName);
		$this->postProcess($request);
	}
}
