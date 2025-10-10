<?php

namespace FreeCRM\Modules\Notification\Models;

/**
 * ListView model for Notification module
 * @package YetiForce.Model
 * @license licenses/License.html
 * @author Tomasz Kur <t.kur@yetiforce.com>
 */
class ListView extends \FreeCRM\Modules\Vtiger\Models\ListView
{

	/**
	 * Function to get the Quick Links for the List view of the module
	 * @param <Array> $linkParams
	 * @return <Array> List of \FreeCRM\Modules\Vtiger\Models\Link instances
	 */
	public function getHederLinks($linkParams)
	{
		$links = \FreeCRM\Modules\Vtiger\Models\Link::getAllByType($this->getModule()->getId(), ['LIST_VIEW_HEADER'], $linkParams);
		$headerLinks = [];
		$userPrivilegesModel = \FreeCRM\Modules\Users\Models\Privileges::getCurrentUserPrivilegesModel();
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
			$links['LIST_VIEW_HEADER'][] = \FreeCRM\Modules\Vtiger\Models\Link::getInstanceFromValues($headerLink);
		}
		return $links;
	}
}
