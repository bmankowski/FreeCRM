<?php

namespace App\Modules\Workflow\Tasks;

use App\Modules\Workflow\VTTask;

/**
 * Watchdog Task Class
 * @package YetiForce.WorkflowTask
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 */

class VTWatchdog extends VTTask
{

	public $executeImmediately = true;
	public $srcWatchdogModule = 'Notification';

	public function getFieldNames(): array
	{
		return ['type', 'message', 'recipients', 'title', 'skipCurrentUser'];
	}

	/**
	 * Execute task
	 * @param \App\Modules\Base\Models\Record $recordModel
	 */
	public function doTask($recordModel)
	{
		$moduleName = $recordModel->getModuleName();
		$recordId = $recordModel->getId();
		$users = [];

		switch ($this->recipients) {
			case 'watchdog':
				$watchdog = \App\Modules\Base\Models\Watchdog::getInstanceById($recordId, $moduleName);
				$users = $watchdog->getWatchingUsers();
				break;
			case 'owner':
				$users = [$recordModel->get('assigned_user_id')];
				break;
			default:
				$users = \App\Security\PrivilegeUtil::getUserByMember($this->recipients);
				break;
		}
		if (empty($users)) {
			return false;
		}
		if (!empty($this->skipCurrentUser) && ($key = array_search((int) (\App\User\CurrentUser::getId() ?? 0), $users)) !== false) {
			unset($users[$key]);
		}
		$relatedField = \App\Core\ModuleHierarchy::getMappingRelatedField($moduleName);
		$notification = \App\Modules\Base\Models\Record::getCleanInstance('Notification');
		$notification->set('shownerid', implode(',', $users));
		$notification->set($relatedField, $recordId);
		$notification->set('title', $this->title);
		$notification->set('description', $this->message);
		$notification->set('notification_type', $this->type);
		$notification->set('notification_status', 'PLL_UNREAD');
		$notification->setHandlerExceptions(['disableHandlers' => true]);
		$notification->save();
	}
}
