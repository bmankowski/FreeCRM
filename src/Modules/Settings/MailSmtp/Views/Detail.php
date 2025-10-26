<?php

namespace App\Modules\Settings\MailSmtp\Views;



/**
 * Mail edit view
 * @package YetiForce.Settings.View
 * @license licenses/License.html
 * @author Adrian Koń <a.kon@yetiforce.com>
 */

class Detail extends \App\Modules\Settings\Vtiger\Views\Index
{

	/**
	 * Checking permission 
	 * @param \App\Http\Vtiger_Request $request
	 * @throws \App\Exceptions\NoPermittedForAdmin
	 */
	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		$currentUserModel = $request->getUser();
		if (!$currentUserModel->isAdmin() || empty($request->get('record'))) {
			throw new \App\Exceptions\NoPermittedForAdmin('LBL_PERMISSION_DENIED');
		}
	}
	
	/**
	 * Process
	 * @param \App\Http\Vtiger_Request $request
	 */
	public function process(\App\Http\Vtiger_Request $request)
	{
		$record = $request->get('record');
		$qualifiedModuleName = $request->getModule(false);
		$recordModel = \App\Modules\Settings\MailSmtp\Models\Record::getInstanceById($record);

		$viewer = $this->getViewer($request);
		$viewer->assign('RECORD_MODEL', $recordModel);
		$viewer->assign('QUALIFIED_MODULE', $qualifiedModuleName);
		$viewer->assign('USER_MODEL', $request->getUser());
		$viewer->view('DetailView.tpl', $qualifiedModuleName);
	}


}
