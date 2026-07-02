<?php

namespace App\Modules\Users\Views;

/**
 * @package YetiForce.Modal
 * @license licenses/License.html
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 */

use App\Http\Vtiger_Request;

class SwitchUsers extends \App\Modules\Base\Views\BasicModal
{

	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		$userModel = $request->getUser();
		if (!$userModel->isAdminUser()) {
			$switchUsers = \App\Modules\Users\Models\Module::getSwitchUsers();
			if (empty($switchUsers)) {
				throw new \App\Exceptions\NoPermitted('LBL_PERMISSION_DENIED');
			}
		}
	}

	public function getSize(\App\Http\Vtiger_Request $request)
	{
		return 'modal-sm';
	}

	public function preProcess(\App\Http\Vtiger_Request $request, $display = true)
	{
		echo '<div class="modal fade switchUsersContainer"><div class="modal-dialog modal-sm"><div class="modal-content">';
	}

	public function getModalScripts(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();

		return $this->checkAndConvertJsScripts([
			"modules.$moduleName.resources.SwitchUsers",
		]);
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$userModel = $request->getUser();
		$users = \App\Modules\Users\Models\Module::getSwitchUsers(true);

		if ($userModel->isAdminUser() && empty($users)) {
			$allUsers = \App\Modules\Users\Models\Record::getAll(true);
			$users = [];
			$dataReader = (new \App\Db\Query())->select(['vtiger_role.rolename', 'vtiger_user2role.userid'])->from('vtiger_role')
					->leftJoin('vtiger_user2role', 'vtiger_role.roleid = vtiger_user2role.roleid')
					->createCommand()->query();
			$roles = [];
			while ($row = $dataReader->read()) {
				$roles[$row['userid']] = $row['rolename'];
			}
			foreach ($allUsers as $userRecord) {
				$userId = $userRecord->getId();
				$users[$userId] = [
					'userName' => $userRecord->getName(),
					'roleName' => $roles[$userId] ?? ''
				];
			}
		}

		$userId = $request->get('id');
		$baseUserId = \App\Http\Vtiger_Session::getRealUserId() ?? $userId;
		unset($users[$baseUserId]);
		unset($users[$userId]);
		$viewer = $this->getViewer($request);
		$viewer->assign('SWITCH_USERS', $users);
		$viewer->assign('MODULE_NAME', $moduleName);
		$viewer->assign('BASE_USER_ID', $baseUserId);
		$this->preProcess($request);
		$viewer->view('SwitchUsers.tpl', $moduleName);
		$this->postProcess($request);
	}
}
