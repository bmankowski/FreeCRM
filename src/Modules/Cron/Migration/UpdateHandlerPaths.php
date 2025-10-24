<?php

namespace App\Modules\Cron\Migration;

/**
 * Migration script to update cron handler file paths
 * @package App\Modules\Cron\Migration
 */
class UpdateHandlerPaths
{
	/**
	 * Execute the migration
	 * @return void
	 */
	public static function execute(): void
	{
		$db = \App\Db::getInstance();
		
		// Path mappings from old to new locations
		$pathMappings = [
			'cron/Mailer.php' => 'src/Modules/Cron/Tasks/Mailer.php',
			'cron/HandlerUpdater.php' => 'src/Modules/Cron/Tasks/HandlerUpdater.php',
			'cron/SendReminder.php' => 'src/Modules/Cron/Tasks/SendReminder.php',
			'cron/PrivilegesUpdater.php' => 'src/Modules/Cron/Tasks/PrivilegesUpdater.php',
			'cron/AddressBook.php' => 'src/Modules/Cron/Tasks/AddressBook.php',
			'cron/LabelUpdater.php' => 'src/Modules/Cron/Tasks/LabelUpdater.php',
			'cron/MultiReference.php' => 'src/Modules/Cron/Tasks/MultiReference.php',
			'cron/Cache.php' => 'src/Modules/Cron/Tasks/Cache.php',
			'cron/Attachments.php' => 'src/Modules/Cron/Tasks/Attachments.php',
			'cron/modules/com_vtiger_workflow/com_vtiger_workflow.php' => 'src/Modules/Cron/Tasks/Workflow/WorkflowScheduler.php',
			'cron/modules/Import/ScheduledImport.php' => 'src/Modules/Import/cron/ScheduledImport.php',
			'cron/modules/Reports/ScheduleReports.php' => 'src/Modules/Reports/cron/ScheduleReports.php',
		];
		
		$updated = 0;
		$notFound = [];
		
		foreach ($pathMappings as $oldPath => $newPath) {
			$result = $db->createCommand()
				->update('vtiger_cron_task', 
					['handler_file' => $newPath], 
					['handler_file' => $oldPath])
				->execute();
			
			if ($result > 0) {
				$updated += $result;
				echo "Updated: $oldPath → $newPath\n";
			} else {
				$notFound[] = $oldPath;
			}
		}
		
		echo "\n=== Migration Summary ===\n";
		echo "Total paths updated: $updated\n";
		
		if (!empty($notFound)) {
			echo "\nPaths not found in database (may already be migrated):\n";
			foreach ($notFound as $path) {
				echo "  - $path\n";
			}
		}
		
		echo "\n=== Current Cron Tasks ===\n";
		$tasks = (new \App\Db\Query())
			->select(['id', 'name', 'handler_file'])
			->from('vtiger_cron_task')
			->orderBy(['sequence' => SORT_ASC])
			->all();
		
		foreach ($tasks as $task) {
			echo sprintf("ID: %d | %s | %s\n", $task['id'], $task['name'], $task['handler_file']);
		}
	}
}

