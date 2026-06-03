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

namespace App\Modules\Cron\Migration;

/**
 * One-time migration: set handler_class for all default cron rows, then drop handler_file (see DropHandlerFileColumn).
 */
final class UpdateAllCronTasksToHandlerClass
{
	/** @var array<int, string> id => FQCN */
	private const ID_TO_CLASS = [
		1 => \App\Modules\Cron\Tasks\WorkflowSchedulerTask::class,
		2 => \App\Modules\Cron\Tasks\AddressBookTask::class,
		3 => \App\Modules\Cron\Tasks\SendReminderTask::class,
		4 => \App\Modules\Settings\CurrencyUpdate\Cron\CurrencyUpdateTask::class,
		5 => \App\Modules\Cron\Tasks\MailerTask::class,
		6 => \App\Modules\Cron\Tasks\HandlerUpdaterTask::class,
		8 => \App\Modules\Import\Cron\ScheduledImportTask::class,
		9 => \App\Modules\Reports\Cron\ScheduleReportsTask::class,
		14 => \App\Modules\API\Cron\CardDavTask::class,
		15 => \App\Modules\API\Cron\CalDavTask::class,
		16 => \App\Modules\Calendar\Cron\ActivityStateTask::class,
		17 => \App\Modules\Cron\Tasks\MultiReferenceTask::class,
		18 => \App\Modules\Calendar\Cron\SetCrmActivityTask::class,
		19 => \App\Modules\Assets\Cron\RenewalTask::class,
		20 => \App\Modules\OSSSoldServices\Cron\RenewalTask::class,
		21 => \App\Modules\Notification\Cron\NotificationsTask::class,
		22 => \App\Modules\Cron\Tasks\LabelUpdaterTask::class,
		23 => \App\Modules\Cron\Tasks\PrivilegesUpdaterTask::class,
		24 => \App\Modules\OpenStreetMap\Cron\UpdaterCoordinatesTask::class,
		25 => \App\Modules\OpenStreetMap\Cron\UpdaterRecordsCoordinatesTask::class,
		26 => \App\Modules\ModTracker\Cron\ReviewChangesTask::class,
		27 => \App\Modules\Cron\Tasks\CacheTask::class,
		28 => \App\Modules\Events\Cron\RecurringEventsTask::class,
		29 => \App\Modules\Cron\Tasks\AttachmentsTask::class,
	];

	public static function execute(): void
	{
		$db = \App\Db\Db::getInstance();
		$updated = 0;
		foreach (self::ID_TO_CLASS as $id => $class) {
			$updated += (int) $db->createCommand()->update('vtiger_cron_task', ['handler_class' => $class], ['id' => $id])->execute();
		}
		echo sprintf("Updated cron task rows (by id): %d\n", $updated);
	}
}
