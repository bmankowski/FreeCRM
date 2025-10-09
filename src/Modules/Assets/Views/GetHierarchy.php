<?php

namespace FreeCRM\Modules\Assets\Views;

/**
 * View to get hierarchy from Account
 * @package YetiForce.actions
 * @license licenses/License.html
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 */

use FreeCRM\Http\Vtiger_Request;
class GetHierarchy extends View
{

	public function checkPermission(\FreeCRM\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$hierarchyModuleName = 'Accounts';

		if (!empty($moduleName)) {
			$userPrivilegesModel = Users_Privileges_Model::getCurrentUserPrivilegesModel();
			$permission = $userPrivilegesModel->hasModulePermission($moduleName);

			$userPrivilegesModel = Users_Privileges_Model::getCurrentUserPrivilegesModel();
			$permissionHierarchyModule = $userPrivilegesModel->hasModulePermission($hierarchyModuleName);

			if (!$permission || !$permissionHierarchyModule) {
				throw new \Exception\NoPermitted('LBL_PERMISSION_DENIED');
			}
		}
	}

	public function process(\FreeCRM\Http\Vtiger_Request $request)
	{
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		$recordId = $request->get('record');
		$fields = $request->get('fields');
		$hierarchyModuleName = 'Accounts';


		$focus = \FreeCRM\CRMEntity::getInstance($hierarchyModuleName);
		$hierarchy = $focus->getAccountHierarchy($recordId, $fields);

		$classFunction = \FreeCRM\AppConfig::module($moduleName, 'RENEWAL_CUSTOMER_FUNCTION');
		$accountIds = [];
		$check = false;
		if ($classFunction && class_exists($classFunction['class']) && method_exists($classFunction['class'], $classFunction['hierarchy'])) {
			$method = $classFunction['hierarchy'];
			$hierarchy = $classFunction['class']::$method($hierarchy);
		}
		foreach ($hierarchy['entries'] as $accountId => $accountInfo) {
			$link = $accountInfo[0]['data'];
			preg_match('/<a href="+/', $link, $matches);
			if (!empty($matches)) {
				preg_match('/[.\s]+/', $link, $dashes);
				preg_match("/<a(.*)>(.*)<\/a>/i", $link, $name);

				$recordModel = Vtiger_Record_Model::getCleanInstance($hierarchyModuleName);
				$recordModel->setId($accountId);
				$hierarchy['entries'][$accountId][0]['data'] = $dashes[0] . $name[2];
			}
		}
		$viewer->assign('MODULE', $hierarchyModuleName);
		$viewer->assign('ACCOUNT_HIERARCHY', $hierarchy);
		$viewer->view('AccountHierarchy.tpl', $hierarchyModuleName);
	}
}
