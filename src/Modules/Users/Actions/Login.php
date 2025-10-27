<?php

namespace App\Modules\Users\Actions;

/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * ********************************************************************************** */




class Login extends \App\Base\Controllers\BaseActionController
{

	public function loginRequired()
	{
		return false;
	}

	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		return true;
	}

	/**
	 * Function verifies application access
	 * @param \App\Http\Vtiger_Request $request
	 */
	public function process(\App\Http\Vtiger_Request $request)
	{
		$username = $request->get('username');
		$password = $request->getRaw('password');
		$moduleModel = \App\Modules\Users\Models\Module::getInstance('Users');
		$bfInstance = class_exists('\App\Modules\Settings\BruteForce\Models\Module') ? \App\Modules\Settings\BruteForce\Models\Module::getCleanInstance() : null;
		if ($bfInstance && $bfInstance->isActive() && $bfInstance->isBlockedIp()) {
			$bfInstance->incAttempts();
			if ($moduleModel) {
				$moduleModel->saveLoginHistory($username, 'Blocked IP');
			}
			header('Location: index.php?module=Users&view=Login&error=2');
			return false;
		}
		$user = \App\CRMEntity::getInstance('Users');
		$user->column_fields['user_name'] = $username;
		if (!empty($password) && $user->doLogin($password)) {
			if (\App\AppConfig::main('session_regenerate_id')) {
				\App\Http\Vtiger_Session::regenerateId(true); // to overcome session id reuse.
			}
			$userId = $user->column_fields['id'];
			
			// Use session method for user ID
			\App\Http\Vtiger_Session::setAuthenticatedUserId($userId);
			\App\Http\Vtiger_Session::set('user_name', $username);
			\App\Http\Vtiger_Session::set('full_user_name', \App\Fields\Owner::getUserLabel($userId, true));
			
			// Attach user to request
			$userModel = \App\Modules\Users\Models\Record::getInstanceById($userId, 'Users');
			$request->setUser($userModel);

			if ($request->has('loginLanguage') && \App\AppConfig::main('langInLoginView')) {
				\App\Http\Vtiger_Session::set('language', $request->get('loginLanguage'));
			}
			if ($request->has('layout')) {
				\App\Http\Vtiger_Session::set('layout', $request->get('layout'));
			}
			//Track the login History
			if ($moduleModel) {
				$moduleModel->saveLoginHistory($user->column_fields['user_name']);
			}
			//End
			if (isset($_SESSION['return_params'])) {
				$return_params = urldecode($_SESSION['return_params']);
				header("Location: index.php?$return_params");
			} else {
				if (\App\AppConfig::performance('SHOW_ADMIN_PANEL') && \App\Modules\Users\Models\Record::getInstanceById($userId, 'Users')->isAdmin()) {
					header('Location: index.php?module=Vtiger&parent=Settings&view=Index');
				} else {
					header('Location: index.php');
				}
			}
		} else {
			if ($bfInstance) {
				$bfInstance->updateBlockedIp();
			}
			$error = 1;
			if ($bfInstance && $bfInstance->isBlockedIp()) {
				$bfInstance->sendNotificationEmail();
				$error = 2;
			}
			//Track the login History
			if ($moduleModel) {
				$moduleModel->saveLoginHistory(\App\Purifier::encodeHtml($request->getRaw('username')), 'Failed login');
			}
			header("Location: index.php?module=Users&view=Login&error=$error");
		}
	}
}
