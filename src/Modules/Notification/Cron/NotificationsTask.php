<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * @project FreeCRM
 * @author bmankowski@gmail.com
 * @copyright (c) FreeCRM
 * @license FreeCRM Public License 1.0
 */

declare(strict_types=1);

namespace App\Modules\Notification\Cron;

use App\Modules\Cron\Tasks\AbstractCronTask;

final class NotificationsTask extends AbstractCronTask
{
	private const MODULE_NAME = 'Notification';

	public function execute(): void
	{
		$db = \App\Database\PearDatabase::getInstance();
		$result = $db->query('SELECT * FROM u_yf_watchdog_schedule');
		while ($row = $db->getRow($result)) {
			$this->executeScheduled($row);
		}
		$this->markAsRead();
	}

	private function executeScheduled(array $row): void
	{
		$currentTime = time();
		$timestampEndDate = empty($row['last_execution']) ? $currentTime : strtotime($row['last_execution'] . ' +' . $row['frequency'] . 'min');
		if ($currentTime >= $timestampEndDate) {
			$endDate = $this->getEndDate($currentTime, $timestampEndDate, $row['frequency']);
			if (\App\Security\Privilege::isPermitted(self::MODULE_NAME, 'ReceivingMailNotifications', false, $row['userid']) && $this->existNotifications($row['userid'], $row['last_execution'], $endDate)) {
				\App\Email\Mailer::sendFromTemplate([
					'template' => 'SendNotificationsViaMail',
					'to' => \App\Modules\Users\Models\Record::getInstanceById($row['userid'], 'Users')->get('email1'),
					'startDate' => $row['last_execution'],
					'endDate' => $endDate,
					'userId' => $row['userid']
				]);
			}
			\App\Db\Db::getInstance()->createCommand()
				->update('u_#__watchdog_schedule', ['last_execution' => $endDate], ['userid' => $row['userid']])
				->execute();
		}
	}

	private function existNotifications($userId, $startDate, $endDate)
	{
		$scheduleData = \App\Modules\Base\Models\Watchdog::getWatchingModulesSchedule($userId, true);
		$modules = $scheduleData['modules'];
		return \App\Modules\Notification\Models\Module::getEmailSendEntries($userId, $modules, $startDate, $endDate, true);
	}

	private function getEndDate($currentTime, $timestampEndDate, $frequency)
	{
		while ($timestampEndDate <= $currentTime && ($nextEndDateTime = $timestampEndDate + ($frequency * 60)) <= $currentTime) {
			$timestampEndDate = $nextEndDateTime;
		}
		return date('Y-m-d H:i:s', $timestampEndDate);
	}

	private function markAsRead(): void
	{
		$notifications = (new \App\Db\Query())
				->select(['smownerid', 'crmid'])
				->from('u_#__notification')
				->innerJoin('vtiger_crmentity', 'u_#__notification.notificationid = vtiger_crmentity.crmid')
				->where(['vtiger_crmentity.deleted' => 0, 'notification_status' => 'PLL_UNREAD'])
				->orderBy(['smownerid' => SORT_ASC, 'createdtime' => SORT_ASC])
				->createCommand()->queryAllByGroup(2);
		foreach ($notifications as $userId => $noticesByUser) {
			$noticesByUser = array_slice($noticesByUser, 0, \App\Core\AppConfig::module('Home', 'MAX_NUMBER_NOTIFICATIONS'));
			foreach ($noticesByUser as $noticeId) {
				$notice = \App\Modules\Base\Models\Record::getInstanceById($noticeId);
				$notice->setMarked();
			}
		}
	}
}
