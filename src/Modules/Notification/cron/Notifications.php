<?php

namespace App\Modules\Notification;

/**
 * Cron - Send notifications via mail
 * @package YetiForce.Cron
 * @license licenses/License.html
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 */
$db = \App\Database\PearDatabase::getInstance();
$notifications = new Cron_Notification();
$result = $db->query('SELECT * FROM u_yf_watchdog_schedule');
while ($row = $db->getRow($result)) {
	$notifications->executeScheduled($row);
}
$notifications->markAsRead();

class Notification {

	const MODULE_NAME = 'Notification';

	/**
	 * Function executes the sending notifications action
	 * @param array $row
	 */
	public function executeScheduled($row)
	{
		$db = \App\Database\PearDatabase::getInstance();
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
			\App\Db::getInstance()->createCommand()
				->update('u_#__watchdog_schedule', ['last_execution' => $endDate], ['userid' => $row['userid']])
				->execute();
		}
	}

	/**
	 * Function checks if notification exists
	 * @param int $userId
	 * @param string $startDate
	 * @param string $endDate
	 * @return int
	 */
	private function existNotifications($userId, $startDate, $endDate)
	{
		$scheduleData = \App\Modules\Base\Models\Watchdog::getWatchingModulesSchedule($userId, true);
		$modules = $scheduleData['modules'];
		return \App\Modules\Notification\Models\Module::getEmailSendEntries($userId, $modules, $startDate, $endDate, true);
	}

	/**
	 * Function get date
	 * @param string $currentTime
	 * @param string $timestampEndDate
	 * @param int $frequency
	 * @return string
	 */
	private function getEndDate($currentTime, $timestampEndDate, $frequency)
	{
		while ($timestampEndDate <= $currentTime && ($nextEndDateTime = $timestampEndDate + ($frequency * 60)) <= $currentTime) {
			$timestampEndDate = $nextEndDateTime;
		}
		return date('Y-m-d H:i:s', $timestampEndDate);
	}

	/**
	 * Function get date
	 * @param string $currentTime
	 * @param string $timestampEndDate
	 * @param int $frequency
	 */
	public function markAsRead()
	{
		$notifications = (new \App\Db\Query())
				->select(['smownerid', 'crmid'])
				->from('u_#__notification')
				->innerJoin('vtiger_crmentity', 'u_#__notification.notificationid = vtiger_crmentity.crmid')
				->where(['vtiger_crmentity.deleted' => 0, 'notification_status' => 'PLL_UNREAD'])
				->orderBy(['smownerid' => SORT_ASC, 'createdtime' => SORT_ASC])
				->createCommand()->queryAllByGroup(2);
		foreach ($notifications as $userId => $noticesByUser) {
			$noticesByUser = array_slice($noticesByUser, 0, \App\AppConfig::module('Home', 'MAX_NUMBER_NOTIFICATIONS'));
			foreach ($noticesByUser as $noticeId) {
				$notice = \App\Modules\Base\Models\Record::getInstanceById($noticeId);
				$notice->setMarked();
			}
		}
	}
}
