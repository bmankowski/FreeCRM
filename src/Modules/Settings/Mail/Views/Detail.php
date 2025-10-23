<?php

namespace App\Modules\Settings\Mail\Views;



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
	 * @throws \Exception\NoPermittedForAdmin
	 */
	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		$currentUserModel = \App\Modules\Users\Models\Record::getCurrentUserModel();
		if (!$currentUserModel->isAdmin() || empty($request->get('record'))) {
			throw new \Exception\NoPermittedForAdmin('LBL_PERMISSION_DENIED');
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
		$recordModel = \App\Modules\Settings\Mail\Models\Record::getInstance($record);
		$viewer = $this->getViewer($request);
		if ($recordModel === false) {
			$moduleModel = new \App\Modules\Settings\Mail\Models\Module();
			$viewer->assign('MODULE_MODEL', $moduleModel);
		}
		$viewer->assign('RECORD_MODEL', $recordModel);
		$viewer->assign('QUALIFIED_MODULE', $qualifiedModuleName);
		$viewer->assign('USER_MODEL', \App\Modules\Users\Models\Record::getCurrentUserModel());
		$viewer->view('DetailView.tpl', $qualifiedModuleName);
	}

	/**
	 * Function to get the list of Script models to be included
	 * @param \App\Http\Vtiger_Request $request
	 * @return array - List of Vtiger_JsScript_Model instances
	 */
	public function getFooterScripts(\App\Http\Vtiger_Request $request)
	{
		$headerScriptInstances = parent::getFooterScripts($request);
		$moduleName = $request->getModule();

		$jsFileNames = [
			'modules.Settings.Vtiger.resources.Detail',
			"modules.Settings.$moduleName.resources.Detail"
		];
		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		$headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);
		return $headerScriptInstances;
	}

	/**
	 * Function to get the page title
	 * @param \App\Http\Vtiger_Request $request
	 * @return string
	 */
	public function getPageTitle(\App\Http\Vtiger_Request $request)
	{
		return 'LBL_MAIL_QUEUE_PAGE_TITLE';
	}
}
