<?php

namespace App\Modules\Settings\WebserviceApps\Views;



/**
 * Create Key
 * @package YetiForce.View
 * @license licenses/License.html
 * @author Tomasz Kur <t.kur@yetiforce.com>
 */

class CreateApp extends \App\Modules\Settings\Vtiger\Views\BasicModal
{

	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		$currentUserModel = \App\Modules\Users\Models\Record::getCurrentUserModel();
		if (!$currentUserModel->isAdminUser()) {
			throw new \Exception\NoPermittedForAdmin('LBL_PERMISSION_DENIED');
		}
	}

	public function getSize(\App\Http\Vtiger_Request $request)
	{
		return 'modal-lg';
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		parent::preProcess($request);
		$moduleName = $request->getModule();
		$qualifiedModuleName = $request->getModule(false);
		$recordId = $request->get('record');
		if (!empty($recordId)) {
			$recordModel = \App\Modules\Settings\WebserviceApps\Models\Record::getInstanceById($recordId);
			$accountId = $recordModel->get('accounts_id');
			if ($recordModel && !empty($accountId)) {
				$recordModel->set('accountsModel', \App\Modules\Vtiger\Models\Record::getInstanceById($accountId));
			}
		} else {
			$recordModel = false;
		}
		$typesServers = \App\Modules\Settings\WebserviceApps\Models\Module::getTypes();
		$viewer = $this->getViewer($request);
		$viewer->assign('MAPPING_RELATED_FIELD', \App\Json::encode(\App\ModuleHierarchy::getRelationFieldByHierarchy('SSingleOrders')));
		$viewer->assign('RECORD_MODEL', $recordModel);
		$viewer->assign('QUALIFIED_MODULE', $qualifiedModuleName);
		$viewer->assign('TYPES_SERVERS', $typesServers);
		$viewer->assign('MODULE', $moduleName);
		$viewer->view('CreateApp.tpl', $qualifiedModuleName);
		parent::postProcess($request);
	}

	public function getModalScripts(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$scripts = array(
			"modules.Settings.$moduleName.resources.Edit",
		);
		$scriptInstances = $this->checkAndConvertJsScripts($scripts);
		return $scriptInstances;
	}
}
