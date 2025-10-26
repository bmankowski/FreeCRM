<?php

namespace App\Modules\KnowledgeBase\Actions;

/**
 * Action to get data of tree
 * @package YetiForce.Action
 * @license licenses/License.html
 * @author Tomasz Kur <t.kur@yetiforce.com>
 */
class DataTreeAjax extends \App\Runtime\BaseActionController
{

	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$userPrivilegesModel = \App\Modules\Users\Models\Privileges::getCurrentUserPrivilegesModel();
		$permission = $userPrivilegesModel->hasModulePermission($moduleName);
		if (!$permission) {
			throw new \App\Exceptions\NoPermitted('LBL_PERMISSION_DENIED');
		}
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$moduleModel = \App\Modules\Base\Models\Module::getInstance($moduleName);
		$treeModel = \App\Modules\KnowledgeBase\Models\Tree::getInstance($moduleModel);
		$allFolders = $treeModel->getFolders();
		$documents = $treeModel->getDocuments();
		if (!is_array($documents)) {
			$documents = [];
		}
		$dataOfTree = array_merge($allFolders, $documents);
		$response = new \App\Http\Vtiger_Response();
		$response->setResult($dataOfTree);
		$response->emit();
	}
}
