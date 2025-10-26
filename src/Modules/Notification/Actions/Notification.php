<?php

namespace App\Modules\Notification\Actions;

/**
 * Notification Action Class
 * @package YetiForce.Action
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.c
 */
class Notification extends \App\Runtime\BaseActionController
{

	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		$id = $request->get('id');
		if (!empty($id)) {
			$notice = \App\Modules\Notification\Models\Record::getInstanceById($id);
			$userPrivilegesModel = \App\Modules\Users\Models\Privileges::getCurrentUserPrivilegesModel();
			if ($userPrivilegesModel->getId() != $notice->getUserId()) {
				throw new \App\Exceptions\NoPermitted('LBL_PERMISSION_DENIED');
			}
		}
		$mode = $request->getMode();
		if ($mode == 'createMessage' && !\App\Modules\Users\Models\Privileges::isPermitted('Notification', 'CreateView')) {
			throw new \App\Exceptions\NoPermitted('LBL_PERMISSION_DENIED');
		} elseif ($mode == 'createMail' && (!\App\Modules\Users\Models\Privileges::isPermitted('Notification', 'NotificationCreateMail') || !\App\AppConfig::main('isActiveSendingMails') || !\App\Modules\Users\Models\Privileges::isPermitted('OSSMail'))) {
			throw new \App\Exceptions\NoPermitted('LBL_PERMISSION_DENIED');
		} elseif (in_array($mode, ['setMark', 'saveWatchingModules']) && !\App\Modules\Users\Models\Privileges::isPermitted('Notification', 'DetailView')) {
			throw new \App\Exceptions\NoPermitted('LBL_PERMISSION_DENIED');
		}
	}

	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct();
		$this->exposeMethod('setMark');
		$this->exposeMethod('saveWatchingModules');
		$this->exposeMethod('createMail');
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$mode = $request->getMode();
		if (!empty($mode)) {
			$this->invokeExposedMethod($mode, $request);
			return;
		}
		throw new \App\Exceptions\NoPermitted('LBL_PERMISSION_DENIED');
	}

	public function setMark(\App\Http\Vtiger_Request $request)
	{
		$ids = $request->get('ids');
		if (!is_array($ids)) {
			$ids = [$ids];
		}
		foreach ($ids as $id) {
			$recordModel = \App\Modules\Vtiger\Models\Record::getInstanceById($id);
			$recordModel->setMarked();
		}

		$response = new \App\Http\Vtiger_Response();
		$response->setResult(true);
		$response->emit();
	}

	public function saveWatchingModules(\App\Http\Vtiger_Request $request)
	{
		$selectedModules = $request->get('selctedModules');
		$watchingModules = \App\Modules\Vtiger\Models\Watchdog::getWatchingModules();
		\App\Modules\Vtiger\Models\Watchdog::setSchedulerByUser($request->get('sendNotifications'), $request->get('frequency'));
		if (!empty($selectedModules)) {
			foreach ($selectedModules as $moduleId) {
				$watchdogModel = \App\Modules\Vtiger\Models\Watchdog::getInstance($moduleId);
				$watchdogModel->changeModuleState(1);
			}
		} else {
			$selectedModules = [];
		}
		foreach ($watchingModules as $moduleId) {
			if (!in_array($moduleId, $selectedModules)) {
				$watchdogModel = \App\Modules\Vtiger\Models\Watchdog::getInstance($moduleId);
				$watchdogModel->changeModuleState(0);
			}
		}
		\App\Modules\Vtiger\Models\Watchdog::reloadCache();
	}

	public function createMail(\App\Http\Vtiger_Request $request)
	{
		$accessibleUsers = \App\Fields\Owner::getInstance()->getAccessibleUsers();
		$content = $request->get('message');
		$subject = $request->get('title');
		$users = $request->get('users');
		if (!is_array($users)) {
			$users = [$users];
		}
		if (count($users)) {
			foreach ($users as $user) {
				if (isset($accessibleUsers[$user])) {
					$email = \App\Modules\Users\Models\Record::getInstanceById($user, 'Users')->getDetail('email1');
					\App\Mailer::addMail([
						//'smtp_id' => 1,
						'to' => [$email => \App\Fields\Owner::getLabel($user)],
						'owner' => $user,
						'subject' => $subject,
						'content' => $content,
					]);
				}
			}
		}
		$response = new \App\Http\Vtiger_Response();
		$response->setResult(true);
		$response->emit();
	}
}
