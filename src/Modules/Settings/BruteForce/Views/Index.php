<?php

namespace App\Modules\Settings\BruteForce\Views;



/**
 * Brute force index view class
 * @package YetiForce.Settings.View
 * @license licenses/License.html
 * @author YetiForce.com
 */

use App\Modules\Settings\BruteForce\Models\Module as Settings_BruteForce_Module_Model;
class Index extends \App\Modules\Settings\Vtiger\Views\Index
{

	/**
	 * Function gets module settings
	 * @param \App\Http\Vtiger_Request $request
	 */
	public function process(\App\Http\Vtiger_Request $request)
	{
		$bfInstance = Settings_BruteForce_Module_Model::getCleanInstance();
		$viewer = $this->getViewer($request);
		$adminUsers = Settings_BruteForce_Module_Model::getAdminUsers();
		$usersForNotifications = Settings_BruteForce_Module_Model::getUsersForNotifications();

		$viewer->assign('MODULE_MODEL', $bfInstance);
		$viewer->assign('CONFIG', $bfInstance->getData());
		$viewer->assign('BLOCKED', $bfInstance->getBlockedIp());
		$viewer->assign('ADMIN_USERS', $adminUsers);
		$viewer->assign('USERS_FOR_NOTIFICATIONS', $usersForNotifications);
		$viewer->view('Index.tpl', $request->getModule(false));
	}
}
