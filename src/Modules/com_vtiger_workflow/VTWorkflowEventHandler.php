<?php

namespace App\Modules\com_vtiger_workflow;

use App\events\VTEventHandler;
/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * ********************************************************************************** */


/*
 * VTEventHandler
 * To remove
 */

class VTWorkflowEventHandler extends VTEventHandler
{
	public $workflows;

	/**
	 * Push tasks to the task queue if the conditions are true
	 * @param $entityData A VTEntityData object representing the entity.
	 */
	function handleEvent($eventName, $eventHandler, $entityCache = false)
	{
		$util = new VTWorkflowUtils();
		$user = $util->adminUser();
		$adb = \App\Database\PearDatabase::getInstance();
		$recordModel = $eventHandler->getRecordModel();
		$entityData = $recordModel; // For now, use record model directly
		$isNew = $recordModel->getPreviousValue() ? false : true;

		if (!$entityCache) {
			$entityCache = new VTEntityCache($user);
		}

		$moduleName = $recordModel->getModuleName();
		$recordId = $recordModel->getId();

		/*
		 * Customer - Feature #10254 Configuring all Email notifications including Ticket notifications
		 * workflows are intialised from ModCommentsHandler.php
		 * While adding a comment on any record which are supporting Comments ModCommentsHandler will trigger
		 */
		if (!is_array($this->workflows)) {
			$wfs = new \App\Modules\com_vtiger_workflow\VTWorkflowManager($adb);
			$this->workflows = $wfs->getWorkflowsForModule($moduleName);
		}
		$workflows = $this->workflows;

		foreach ($workflows as $workflow) {
			if (!is_object($workflow) || get_class($workflow) !== 'Workflow')
				continue;
			switch ($workflow->executionCondition) {
				case \App\Modules\com_vtiger_workflow\VTWorkflowManager::$ON_FIRST_SAVE: {
						if ($isNew) {
							$doEvaluate = true;
						} else {
							$doEvaluate = false;
						}
						break;
					}
				case \App\Modules\com_vtiger_workflow\VTWorkflowManager::$ONCE: {
						if ($workflow->isCompletedForRecord($recordId)) {
							$doEvaluate = false;
						} else {
							$doEvaluate = true;
						}
						break;
					}
				case \App\Modules\com_vtiger_workflow\VTWorkflowManager::$ON_EVERY_SAVE: {
						$doEvaluate = true;
						break;
					}
				case \App\Modules\com_vtiger_workflow\VTWorkflowManager::$ON_MODIFY: {
						// Check if record was modified (not newly created)
						$doEvaluate = !$isNew;
						break;
					}
				case \App\Modules\com_vtiger_workflow\VTWorkflowManager::$MANUAL: {
						$doEvaluate = false;
						break;
					}
				case \App\Modules\com_vtiger_workflow\VTWorkflowManager::$ON_SCHEDULE: {
						$doEvaluate = false;
						break;
					}
				case \App\Modules\com_vtiger_workflow\VTWorkflowManager::$ON_DELETE: {
						$doEvaluate = false;
						break;
					}
				case \App\Modules\com_vtiger_workflow\VTWorkflowManager::$TRIGGER: {
						$doEvaluate = false;
						break;
					}
				case \App\Modules\com_vtiger_workflow\VTWorkflowManager::$BLOCK_EDIT: {
						$doEvaluate = false;
						break;
					}
				case \App\Modules\com_vtiger_workflow\VTWorkflowManager::$ON_RELATED: {
						$doEvaluate = false;
						break;
					}
				default: {
						throw new Exception("Should never come here! Execution Condition:" . $workflow->executionCondition);
					}
			}
			if ($doEvaluate && $workflow->evaluate($entityCache, $recordId)) {
				if (\App\Modules\com_vtiger_workflow\VTWorkflowManager::$ONCE == $workflow->executionCondition) {
					$workflow->markAsCompletedForRecord($recordId);
				}

				$workflow->performTasks($recordModel);
			}
		}
		$util->revertUser();
	}
}
