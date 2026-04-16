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

use App\Modules\Workflow\VTTaskManager;
use App\Modules\Workflow\VTTaskQueue;
use App\Modules\Workflow\WorkFlowScheduler;

final class WorkflowSchedulerTask extends AbstractCronTask
{
	public function execute(): void
	{
		$adb = \App\Db\Db::getInstance();
		$workflowScheduler = new WorkFlowScheduler($adb);
		$workflowScheduler->queueScheduledWorkflowTasks();
		$readyTasks = (new VTTaskQueue($adb))->getReadyTasks();
		$tm = new VTTaskManager($adb);

		foreach ($readyTasks as $taskDetails) {
			[$taskId, $entityId, $taskContents] = $taskDetails;
			$task = $tm->retrieveTask($taskId);
			if (empty($task)) {
				continue;
			}
			$task->setContents($taskContents);
			$task->doTask(\App\Modules\Base\Models\Record::getInstanceById($entityId));
		}
	}
}
