<?php

namespace App\Modules\Users\Actions;

/**
 * Switch Users Action Class
 * @package YetiForce.Action
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */
class SwitchUsers extends \App\Base\Controllers\BaseActionController
{

	/**
	 * Function checks permissions
	 * @param \App\Http\Vtiger_Request $request
	 * @throws \App\Exceptions\NoPermitted
	 */
	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		$userId = $request->get('id');
		$currentUserModel = $request->getUser();
		$baseUserId = $currentUserModel->getRealId();
		$currentUserId = $currentUserModel->getId();
		
		// Always allow switching back to original user (baseUserId)
		// This handles the "switch to yourself" case when user is already switched
		if ($userId == $baseUserId) {
			// Verify that the target user exists
			$targetUser = \App\Modules\Users\Models\Record::getInstanceById($userId, 'Users');
			if (!$targetUser || !$targetUser->getId()) {
				throw new \App\Exceptions\NoPermitted('LBL_PERMISSION_DENIED');
			}
			return; // Can always switch back to original user
		}
		
		// Allow admin users to switch to any user without checking switchUsers.php
		if ($currentUserModel->isAdminUser()) {
			// Verify that the target user exists and is active
			$targetUser = \App\Modules\Users\Models\Record::getInstanceById($userId, 'Users');
			if (!$targetUser || !$targetUser->getId()) {
				throw new \App\Exceptions\NoPermitted('LBL_PERMISSION_DENIED');
			}
			return; // Admin can switch to any user
		}
		
		// For non-admin users, check switchUsers.php configuration
		require('user_privileges/switchUsers.php');
		if (!key_exists($baseUserId, $switchUsers) || !key_exists($userId, $switchUsers[$baseUserId])) {
			$db = \App\Db\Db::getInstance('log');
			$db->createCommand()->insert('l_#__switch_users', [
				'baseid' => $baseUserId,
				'destid' => $userId,
				'busername' => $currentUserModel->getName(),
				'dusername' => '',
				'date' => date('Y-m-d H:i:s'),
				'ip' => \App\Utils\RequestUtil::getRemoteIP(),
				'agent' => $_SERVER['HTTP_USER_AGENT'],
				'status' => 'No permission',
			])->execute();
			throw new \App\Exceptions\NoPermitted('LBL_PERMISSION_DENIED');
		}
	}

	/**
	 * Function proccess
	 * @param \App\Http\Vtiger_Request $request
	 */
	public function process(\App\Http\Vtiger_Request $request)
	{
		$currentUserModel = $request->getUser();
		$baseUserId = $currentUserModel->getRealId();
		$userId = $request->get('id');
		$user = new \App\Modules\Users\Users();
		$targetUser = $user->retrieveCurrentUserInfoFromFile($userId);
		$targetUserName = $targetUser->column_fields['user_name'];
		$targetUserFullName = $targetUser->column_fields['first_name'] . ' ' . $targetUser->column_fields['last_name'];
		\App\Http\Vtiger_Session::setAuthenticatedUserId((int) $userId);
		\App\Http\Vtiger_Session::set('user_name', $targetUserName);
		\App\Http\Vtiger_Session::set('full_user_name', trim($targetUserFullName));

		$status = 'Switched';
		if (!\App\Http\Vtiger_Session::isImpersonated()) {
			\App\Http\Vtiger_Session::set('baseUserId', $baseUserId);
			$status = 'Signed in';
		} elseif ($userId === \App\Http\Vtiger_Session::getRealUserId()) {
			$baseUserId = $userId;
			\App\Http\Vtiger_Session::set('baseUserId', '');
			$status = 'Signed out';
		} else {
			$baseUserId = \App\Http\Vtiger_Session::getRealUserId();
		}

		$db = \App\Db\Db::getInstance('log');
		$baseUserModel = \App\Modules\Users\Models\Record::getInstanceById($baseUserId, 'Users');
		$db->createCommand()->insert('l_#__switch_users', [
			'baseid' => $baseUserId,
			'destid' => $userId,
			'busername' => $baseUserModel->getName(),
			'dusername' => trim($targetUserFullName),
			'date' => date('Y-m-d H:i:s'),
			'ip' => \App\Utils\RequestUtil::getRemoteIP(),
			'agent' => $_SERVER['HTTP_USER_AGENT'],
			'status' => $status,
		])->execute();

		header('Location: ' . $this->getReturnUrlForSwitchedUsers($request));
	}

	/**
	 * Resolve safe redirect target after user switch (stay on current page when possible).
	 */
	protected function getReturnUrlForSwitchedUsers(\App\Http\Vtiger_Request $request): string
	{
		$returnUrl = trim((string) $request->get('returnUrlForSwitchedUsers', ''));
		if ($returnUrl === '') {
			return 'index.php';
		}
		$returnUrl = ltrim($returnUrl, '/');
		if (preg_match('#^(https?:)?//#i', $returnUrl) || strpos($returnUrl, 'index.php') !== 0) {
			return 'index.php';
		}
		return $returnUrl;
	}
}
