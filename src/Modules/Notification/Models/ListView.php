<?php

namespace App\Modules\Notification\Models;

/**
 * ListView model for Notification module
 * @package YetiForce.Model
 * @license licenses/License.html
 * @author Tomasz Kur <t.kur@yetiforce.com>
 */
class ListView extends \App\Modules\Base\Models\ListView
{

	/**
	 * Function to get the Quick Links for the List view of the module
	 * @param <Array> $linkParams
	 * @return <Array> List of \App\Modules\Base\Models\Link instances
	 */
	public function getHederLinks($linkParams)
	{
		$links = \App\Modules\Base\Models\Link::getAllByType($this->getModule()->getId(), ['LIST_VIEW_HEADER'], $linkParams);
		$headerLinks = [];
		$userPrivilegesModel = \App\Modules\Users\Models\Privileges::getCurrentUserPrivilegesModel();
		if ($userPrivilegesModel->hasModulePermission('Notification') && $userPrivilegesModel->hasModuleActionPermission('Notification', 'CreateView')) {
			$headerLinks[] = [
				'linktype' => 'LIST_VIEW_HEADER',
				'linkhint' => 'LBL_NOTIFICATION_SETTINGS',
				'linkurl' => 'index.php?module=Notification&view=NotificationConfig',
				'linkicon' => 'glyphicon glyphicon-cog',
				'modalView' => true
			];
		}
		if ($userPrivilegesModel->hasModulePermission('Notification') && $userPrivilegesModel->hasModuleActionPermission('Notification', 'CreateView')) {
			$headerLinks[] = [
				'linktype' => 'LIST_VIEW_HEADER',
				'linkhint' => 'LBL_SEND_NOTIFICATION',
				'linkurl' => 'javascript:Vtiger_Index_Js.sendNotification(this)',
				'linkicon' => 'glyphicon glyphicon-send'
			];
		}
		foreach ($headerLinks as $headerLink) {
			$links['LIST_VIEW_HEADER'][] = \App\Modules\Base\Models\Link::getInstanceFromValues($headerLink);
		}
		return $links;
	}
}
