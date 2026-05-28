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

final class MigrateKandydaciScheduledImportToClass
{
	public static function execute(): void
	{
		$db = \App\Db\Db::getInstance();

		$handlerClass = 'App\\Modules\\Kandydaci\\Cron\\ScheduledImportTask';

		$updated = 0;
		try {
			$updated += (int) $db->createCommand()
				->update('vtiger_cron_task', ['handler_class' => $handlerClass], ['module' => 'Kandydaci'])
				->execute();
		} catch (\Throwable $e) {
			\App\Log\Log::error($e, 'CRON');
		}

		echo sprintf("Updated Kandydaci cron tasks: %d\n", (int) $updated);
	}
}

