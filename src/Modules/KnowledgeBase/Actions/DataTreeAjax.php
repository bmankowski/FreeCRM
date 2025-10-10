<?php

namespace FreeCRM\Modules\KnowledgeBase\Actions;

/**
 * Action to get data of tree
 * @package YetiForce.Action
 * @license licenses/License.html
 * @author Tomasz Kur <t.kur@yetiforce.com>
 */
class DataTreeAjax extends \FreeCRM\Runtime\Vtiger_Action_Controller
{

	public function checkPermission(\FreeCRM\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$userPrivilegesModel = \FreeCRM\Modules\Users\Models\Privileges::getCurrentUserPrivilegesModel();
		$permission = $userPrivilegesModel->hasModulePermission($moduleName);
		if (!$permission) {
			throw new \Exception\NoPermitted('LBL_PERMISSION_DENIED');
		}
	}

	public function process(\FreeCRM\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$moduleModel = \FreeCRM\Modules\Vtiger\Models\Module::getInstance($moduleName);
		$treeModel = KnowledgeBase_Tree_Model::getInstance($moduleModel);
		$allFolders = $treeModel->getFolders();
		$documents = $treeModel->getDocuments();
		if (!is_array($documents)) {
			$documents = [];
		}
		$dataOfTree = array_merge($allFolders, $documents);
		$response = new \FreeCRM\Http\Vtiger_Response();
		$response->setResult($dataOfTree);
		$response->emit();
	}
}
