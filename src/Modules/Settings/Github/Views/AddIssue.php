<?php

namespace App\Modules\Settings\Github\Views;



/**
 * Show modal to add issue 
 * @package YetiForce.View
 * @license licenses/License.html
 * @author Tomasz Kur <t.kur@yetiforce.com>
 */
class AddIssue extends \App\Modules\Vtiger\Views\BasicModal
{

	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		$currentUserModel = $request->getUser();
		if (!$currentUserModel->isAdminUser()) {
			throw new \Exception\NoPermittedForAdmin('LBL_PERMISSION_DENIED');
		}
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$qualifiedModule = $request->getModule(false);
		$viewer = $this->getViewer($request);
		$clientModel = \App\Modules\Settings\Github\Models\Client::getInstance();
		$configuration = \App\Modules\Settings\ConfReport\Models\Module::getConfigurationValue();
		$libraries = \App\Modules\Settings\ConfReport\Models\Module::getConfigurationLibrary();
		$errorLibraries = [];
		foreach ($libraries as $key => $value) {
			if ($value['status'] == 'LBL_NO') {
				$errorLibraries[$key] = $value;
			}
		}
		$errorConfig = [];
		foreach ($configuration as $key => $value) {
			if ($value['status']) {
				$errorConfig[$key] = $value;
			}
		}
		$phpVersion = PHP_VERSION;
		$viewer->assign('GITHUB_CLIENT_MODEL', $clientModel);
		$viewer->assign('PHP_VERSION', $phpVersion);
		$viewer->assign('ERROR_CONFIGURATION', $errorConfig);
		$viewer->assign('ERROR_LIBRARIES', $errorLibraries);
		$viewer->assign('QUALIFIED_MODULE', $qualifiedModule);
		$viewer->view('AddIssueModal.tpl', $qualifiedModule);
	}
}
