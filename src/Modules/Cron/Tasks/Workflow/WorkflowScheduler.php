<?php
/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

use App\Modules\Workflow\WorkFlowScheduler;
use App\Modules\Workflow\VTTaskQueue;
use App\Modules\Workflow\VTTaskManager;
use App\Db;

$adb = Db::getInstance();
$workflowScheduler = new WorkFlowScheduler($adb);
$workflowScheduler->queueScheduledWorkflowTasks();
$readyTasks = (new VTTaskQueue($adb))->getReadyTasks();
$tm = new VTTaskManager($adb);

foreach ($readyTasks as $taskDetails) {
	list($taskId, $entityId, $taskContents) = $taskDetails;
	$task = $tm->retrieveTask($taskId);
	// If task is not there then continue
	if (empty($task)) {
		continue;
	}
	$task->setContents($taskContents);
	$task->doTask(\App\Modules\Vtiger\Models\Record::getInstanceById($entityId));
}
