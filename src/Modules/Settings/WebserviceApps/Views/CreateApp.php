<?php

namespace App\Modules\Settings\WebserviceApps\Views;



/**
 * Create Key
 * @package YetiForce.View
 * @license licenses/License.html
 * @author Tomasz Kur <t.kur@yetiforce.com>
 */

class CreateApp extends \App\Modules\Settings\Base\Views\BasicModal
{

	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		$currentUserModel = $request->getUser();
		if (!$currentUserModel->isAdminUser()) {
			throw new \App\Exceptions\NoPermittedForAdmin('LBL_PERMISSION_DENIED');
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
				$recordModel->set('accountsModel', \App\Modules\Base\Models\Record::getInstanceById($accountId));
			}
		} else {
			$recordModel = false;
		}
		$typesServers = \App\Modules\Settings\WebserviceApps\Models\Module::getTypes();
		$viewer = $this->getViewer($request);
		$viewer->assign('RECORD_MODEL', $recordModel);
		$viewer->assign('QUALIFIED_MODULE', $qualifiedModuleName);
		$viewer->assign('TYPES_SERVERS', $typesServers);
		$viewer->assign('MODULE', $moduleName);
		
		// Prepare CreateApp-specific data for CreateApp template
		$this->prepareWebserviceAppsCreateAppData($viewer);
		
		$viewer->view('CreateApp.tpl', $qualifiedModuleName);
		parent::postProcess($request);
	}
	
	/**
	 * Prepare data for WebserviceApps CreateApp template
	 * Moves function calls from template to controller for better MVC separation
	 */
	protected function prepareWebserviceAppsCreateAppData($viewer)
	{
		$mappingRelatedField = \App\ModuleHierarchy::getRelationFieldByHierarchy('SSingleOrders');
		$viewer->assign('MAPPING_RELATED_FIELD', \App\Modules\Base\Helpers\Util::toSafeHTML(\App\Utils\Json::encode($mappingRelatedField)));
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
