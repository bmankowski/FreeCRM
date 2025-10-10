<?php

namespace FreeCRM\Modules\Notification\Dashboards;

/**
 * Notifications Dashboard Class
 * @package YetiForce.Dashboard
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */
use FreeCRM\Http\Vtiger_Request;

class Notifications extends \Vtiger_Index_View
{

	public function process(Vtiger_Request $request)
	{
		$currentUser = \FreeCRM\Modules\Users\Models\Record::getCurrentUserModel();
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		$widget = \FreeCRM\Modules\Vtiger\Models\Widget::getInstance($request->get('linkid'), $currentUser->getId());
		$limit = (int) $widget->get('limit');
		if (empty($limit)) {
			$limit = 10;
		}
		$type = $request->get('type');
		$condition = false;
		if (!empty($type)) {
			$condition = ['u_#__notification.notification_type' => $type];
		}
		$notificationModel = \FreeCRM\Modules\Notification\Models\Module::getInstance($moduleName);
		$notifications = $notificationModel->getEntries($limit, $condition);

		$typesNotification = $notificationModel->getTypes();
		array_unshift($typesNotification, \LanguageTranslator::translate('All'));
		$viewer->assign('TYPES_NOTIFICATION', $typesNotification);
		$viewer->assign('NOTIFICATIONS', $notifications);
		$viewer->assign('WIDGET', $widget);
		$viewer->assign('MODULE_NAME', $moduleName);
		$content = $request->get('content');
		if (!empty($content)) {
			$viewer->view('dashboards/NotificationsContents.tpl', $moduleName);
		} else {
			$viewer->view('dashboards/Notifications.tpl', $moduleName);
		}
	}
}
