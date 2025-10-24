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

// Initialize webservices (required for workflow operations)
require_once("include/Webservices/State.php");
require_once("include/Webservices/OperationManager.php");
require_once("include/Webservices/SessionManager.php");
require_once("include/Webservices/VtigerCRMObject.php");
require_once("include/Webservices/VtigerCRMObjectMeta.php");
require_once("include/Webservices/DataTransform.php");
require_once("include/Webservices/WebServiceError.php");

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
