<?php

namespace App\Modules\Settings\ConfReport\Views;



/**
 * Speed test view class
 * @package YetiForce.View
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */
class Speed extends \App\Modules\Vtiger\Views\BasicModal
{

	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		$currentUserModel = $request->getUser();
		if (!$currentUserModel->isAdminUser()) {
			throw new \App\Exceptions\NoPermittedForAdmin('LBL_PERMISSION_DENIED');
		}
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$qualifiedModule = $request->getModule(false);
		$viewer = $this->getViewer($request);
		$viewer->assign('TESTS', \App\Modules\Settings\ConfReport\Models\Module::testSpeed());
		$viewer->assign('QUALIFIED_MODULE', $qualifiedModule);
		$viewer->view('Speed.tpl', $qualifiedModule);
	}
}
