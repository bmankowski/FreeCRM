<?php

namespace App\Modules\Users\Handlers;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * *********************************************************************************** */

class Users_ForgotPassword_Handler {

	public function changePassword($data)
	{
		$site_URL = rtrim(\App\Core\AppConfig::main('site_URL'), '/');
		$request = new \App\Http\Vtiger_Request($data);
		$userName = $request->get('username');
		$viewer = CRM_Viewer::getInstance();
		$companyModel = \App\Core\Company::getInstanceById();
		$logo = $companyModel->getLogo();
		$moduleName = 'Users';
		$viewer->assign('LOGOURL', $logo ? $logo->get('imageUrl') : '');
		$viewer->assign('TITLE', $logo ? $logo->get('title') : $companyModel->get('name'));
		$viewer->assign('USERNAME', $userName);
		$changePasswordTrackUrl = $site_URL . "/modules/Users/Actions/ForgotPassword.php";
		$viewer->assign('TRACKURL', $changePasswordTrackUrl);
		$expiryTime = (int) $request->get('time') + (24 * 60 * 60);
		$currentTime = time();
		if ($expiryTime > $currentTime) {
			$secretToken = uniqid();
			$secretHash = md5($userName . $secretToken);
			$options = array(
				'handler_path' => 'src/Modules/Users/handlers/ForgotPassword.php',
				'handler_class' => 'Users_ForgotPassword_Handler',
				'handler_function' => 'changePassword',
				'onetime' => 1,
				'handler_data' => array(
					'username' => $userName,
					'secret_token' => $secretToken,
					'secret_hash' => $secretHash
				)
			);
			$trackURL = \App\Modules\Base\Helpers\ShortURL::generateURL($options);
			$shortURLID = explode('id=', $trackURL);
			$viewer->assign('SHORTURL_ID', $shortURLID[1]);
			$viewer->assign('SECRET_HASH', $secretHash);
		} else {
			$viewer->assign('LINK_EXPIRED', true);
		}

		$viewer->assign('TRACKURL', $changePasswordTrackUrl);
		$viewer->assign('MODULE', $moduleName);
		$viewer->view('ForgotPassword.tpl', $moduleName);
	}
}
