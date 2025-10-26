<?php

namespace App\Modules\Settings\BruteForce\Views;



/**
 * Brute force index view class
 * @package YetiForce.Settings.View
 * @license licenses/License.html
 * @author YetiForce.com
 */

class Index extends \App\Modules\Settings\Base\Views\Index
{

	/**
	 * Function gets module settings
	 * @param \App\Http\Vtiger_Request $request
	 */
	public function process(\App\Http\Vtiger_Request $request)
	{
		$bfInstance = \App\Modules\Settings\BruteForce\Models\Module::getCleanInstance();
		$viewer = $this->getViewer($request);
		$adminUsers = \App\Modules\Settings\BruteForce\Models\Module::getAdminUsers();
		$usersForNotifications = \App\Modules\Settings\BruteForce\Models\Module::getUsersForNotifications();

		$viewer->assign('MODULE_MODEL', $bfInstance);
		$viewer->assign('CONFIG', $bfInstance->getData());
		$viewer->assign('BLOCKED', $bfInstance->getBlockedIp());
		$viewer->assign('ADMIN_USERS', $adminUsers);
		$viewer->assign('USERS_FOR_NOTIFICATIONS', $usersForNotifications);
		$viewer->view('Index.tpl', $request->getModule(false));
	}
}
