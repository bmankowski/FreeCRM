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

use Throwable;

/**
 * Irreversible migration helper.
 *
 * Drops legacy vtiger_cron_task.handler_file column after 100% migration to handler_class.
 */
final class DropHandlerFileColumn
{
	public static function execute(): void
	{
		$db = \App\Db\Db::getInstance();

		try {
			$db->createCommand('ALTER TABLE `vtiger_cron_task` DROP COLUMN `handler_file`')->execute();
			echo "Dropped column vtiger_cron_task.handler_file\n";
		} catch (Throwable $e) {
			\App\Log\Log::error($e, 'CRON');
			echo "Failed to drop column vtiger_cron_task.handler_file: " . $e->getMessage() . "\n";
		}
	}
}

