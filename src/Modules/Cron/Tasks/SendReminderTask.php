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

namespace App\Modules\Cron\Tasks;

final class SendReminderTask extends AbstractCronTask
{
	public function execute(): void
	{
		$adb = \App\Database\PearDatabase::getInstance();
		\App\Log\Log::trace(' Start SendReminder ');

		$query = "SELECT vtiger_crmentity.crmid, vtiger_crmentity.smownerid, vtiger_activity.*, vtiger_activity_reminder.reminder_time, vtiger_activity_reminder.reminder_sent, vtiger_crmentity.setype AS crmsetype
FROM vtiger_activity 
INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid=vtiger_activity.activityid 
INNER JOIN vtiger_activity_reminder ON vtiger_activity.activityid=vtiger_activity_reminder.activity_id 
WHERE DATE_FORMAT(vtiger_activity.date_start,'%Y-%m-%d, %H:%i:%s') >= ? 
AND vtiger_crmentity.crmid != 0 
AND vtiger_activity.status = 'PLL_PLANNED' 
AND vtiger_activity_reminder.reminder_sent = 0 
GROUP BY vtiger_activity.activityid";

		$result = $adb->pquery($query, [date('Y-m-d')]);
		if ($adb->getRowCount($result) >= 1) {
			$reminderFrequency = (new \App\Db\Query())->select('frequency')
				->from('vtiger_cron_task')
				->where(['name' => 'LBL_SEND_REMINDER'])
				->scalar();

			$eventsRecordModel = \App\Modules\Base\Models\Record::getCleanInstance('Events');

			while ($row = $adb->getRow($result)) {
				$date_start = $row['date_start'];
				$time_start = $row['time_start'];
				$reminder_time = $row['reminder_time'] * 60;
				$date = new \App\Fields\DateTimeField(null);
				$userFormatedString = $date->getDisplayDate();
				$timeFormatedString = $date->getDisplayTime();
				$dBFomatedDate = \App\Fields\DateTimeField::convertToDBFormat($userFormatedString);
				$curr_time = strtotime("$dBFomatedDate $timeFormatedString");
				$activityId = $row['activityid'];

				$date = new \App\Fields\DateTimeField("$date_start $time_start");
				$userFormatedString = $date->getDisplayDate();
				$timeFormatedString = $date->getDisplayTime();
				$dBFomatedDate = \App\Fields\DateTimeField::convertToDBFormat($userFormatedString);
				$activity_time = strtotime("$dBFomatedDate $timeFormatedString");
				$differenceOfActivityTimeAndCurrentTime = ($activity_time - $curr_time);

				if (($differenceOfActivityTimeAndCurrentTime > 0) && (($differenceOfActivityTimeAndCurrentTime <= $reminder_time) || ($differenceOfActivityTimeAndCurrentTime <= $reminderFrequency))) {
					\App\Log\Log::trace('Start Send SendReminder');
					$toEmail = \App\Fields\Email::getUserMail($row['smownerid']);
					$invitees = [];

					if ($row['activitytype'] == 'Task') {
						$template = 'ActivityReminderNotificationTask';
					} else {
						$template = 'ActivityReminderNotificationEvents';
						$eventsRecordModel->set('id', $activityId);
						if (\App\Core\AppConfig::module('Calendar', 'SEND_REMINDER_INVITATION')) {
							$invitees = $eventsRecordModel->getInvities();
						}
					}
					if (!empty($toEmail)) {
						\App\Email\Mailer::sendFromTemplate([
							'template' => $template,
							'moduleName' => 'Calendar',
							'recordId' => $activityId,
							'to' => $toEmail,
						]);
						$params = ['reminder_sent' => 1];
						$adb->update('vtiger_activity_reminder', $params, 'activity_id = ?', [$activityId]);
					}
					foreach ($invitees as &$invitation) {
						if (!empty($invitation['email'])) {
							\App\Email\Mailer::sendFromTemplate([
								'template' => 'ActivityReminderNotificationInvitation',
								'moduleName' => 'Calendar',
								'recordId' => $activityId,
								'to' => $invitation['email'],
							]);
						}
					}
					\App\Log\Log::trace('End Send SendReminder');
				}
			}
		}
	}
}
