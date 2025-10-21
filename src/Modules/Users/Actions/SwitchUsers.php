<?php

namespace App\Modules\Users\Actions;

/**
 * Switch Users Action Class
 * @package YetiForce.Action
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */
class SwitchUsers extends \App\Runtime\Vtiger_Action_Controller
{

	/**
	 * Function checks permissions
	 * @param \App\Http\Vtiger_Request $request
	 * @throws \Exception\NoPermitted
	 */
	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		$userId = $request->get('id');
		require('user_privileges/switchUsers.php');
		$currentUserModel = \App\Modules\Users\Models\Record::getCurrentUserModel();
		$baseUserId = $currentUserModel->getRealId();
		if (!key_exists($baseUserId, $switchUsers) || !key_exists($userId, $switchUsers[$baseUserId])) {
			$db = \App\Db::getInstance('log');
			$db->createCommand()->insert('l_#__switch_users', [
				'baseid' => $baseUserId,
				'destid' => $userId,
				'busername' => $currentUserModel->getName(),
				'dusername' => '',
				'date' => date('Y-m-d H:i:s'),
				'ip' => \App\RequestUtil::getRemoteIP(),
				'agent' => $_SERVER['HTTP_USER_AGENT'],
				'status' => 'Failed login - No permission',
			])->execute();
			throw new \Exception\NoPermitted('LBL_PERMISSION_DENIED');
		}
	}

	/**
	 * Function proccess
	 * @param \App\Http\Vtiger_Request $request
	 */
	public function process(\App\Http\Vtiger_Request $request)
	{
		$currentUserModel = \App\Modules\Users\Models\Record::getCurrentUserModel();
		$baseUserId = $currentUserModel->getId();
		$userId = $request->get('id');
		$user = new \App\Modules\Users\Users();
		$currentUser = $user->retrieveCurrentUserInfoFromFile($userId);
		$name = $currentUserModel->getName();
		$userName = $currentUser->column_fields['user_name'];
		\App\Http\Vtiger_Session::set('authenticated_user_id', $userId);
		\App\Http\Vtiger_Session::set('user_name', $userName);
		\App\Http\Vtiger_Session::set('full_user_name', $name);

		$status = 'Switched';
		if (empty(\App\Http\Vtiger_Session::get('baseUserId'))) {
			\App\Http\Vtiger_Session::set('baseUserId', $baseUserId);
			$status = 'Signed in';
		} elseif ($userId === \App\Http\Vtiger_Session::get('baseUserId')) {
			$baseUserId = $userId;
			\App\Http\Vtiger_Session::set('baseUserId', '');
			$status = 'Signed out';
		} else {
			$baseUserId = \App\Http\Vtiger_Session::get('baseUserId');
		}

		$db = \App\Db::getInstance('log');
		$db->createCommand()->insert('l_#__switch_users', [
			'baseid' => $baseUserId,
			'destid' => $userId,
			'busername' => $currentUserModel->getName(),
			'dusername' => $name,
			'date' => date('Y-m-d H:i:s'),
			'ip' => \App\RequestUtil::getRemoteIP(),
			'agent' => $_SERVER['HTTP_USER_AGENT'],
			'status' => $status,
		])->execute();

		header('Location: index.php');
	}
}
