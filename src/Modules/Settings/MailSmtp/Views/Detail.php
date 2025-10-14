<?php

namespace FreeCRM\Modules\Settings\MailSmtp\Views;



/**
 * Mail edit view
 * @package YetiForce.Settings.View
 * @license licenses/License.html
 * @author Adrian Koń <a.kon@yetiforce.com>
 */

use FreeCRM\Modules\Settings\MailSmtp\Models\Record as Settings_MailSmtp_Record_Model;
class Detail extends \FreeCRM\Modules\Settings\Vtiger\Views\Index
{

	/**
	 * Checking permission 
	 * @param \FreeCRM\Http\Vtiger_Request $request
	 * @throws \Exception\NoPermittedForAdmin
	 */
	public function checkPermission(\FreeCRM\Http\Vtiger_Request $request)
	{
		$currentUserModel = \App\User::getCurrentUserModel();
		if (!$currentUserModel->isAdmin() || empty($request->get('record'))) {
			throw new \Exception\NoPermittedForAdmin('LBL_PERMISSION_DENIED');
		}
	}
	
	/**
	 * Process
	 * @param \FreeCRM\Http\Vtiger_Request $request
	 */
	public function process(\FreeCRM\Http\Vtiger_Request $request)
	{
		$record = $request->get('record');
		$qualifiedModuleName = $request->getModule(false);
		$recordModel = Settings_MailSmtp_Record_Model::getInstanceById($record);

		$viewer = $this->getViewer($request);
		$viewer->assign('RECORD_MODEL', $recordModel);
		$viewer->assign('QUALIFIED_MODULE', $qualifiedModuleName);
		$viewer->assign('USER_MODEL', Users_Record_Model::getCurrentUserModel());
		$viewer->view('DetailView.tpl', $qualifiedModuleName);
	}


}
