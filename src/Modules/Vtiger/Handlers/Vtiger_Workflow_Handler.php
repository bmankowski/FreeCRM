<?php

namespace App\Modules\Vtiger\Handlers;


/**
 * Workflow handler
 * @package YetiForce.Handler
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */
class Vtiger_Workflow_Handler {

	private $workflows;

	public function entityAfterRestore(\App\EventHandler $eventHandler)
	{
		$this->entityAfterSave($eventHandler);
	}

	/**
	 * EntityAfterSave function
	 * @param \App\EventHandler $eventHandler
	 */
	public function entityAfterSave(\App\EventHandler $eventHandler)
	{
		$recordModel = $eventHandler->getRecordModel();
		$recordId = $recordModel->getId();
		$isNew = $recordModel->isNew();
		if (!isset($this->workflows)) {
			$wfs = new \App\Modules\com_vtiger_workflow\VTWorkflowManager();
			$this->workflows = $wfs->getWorkflowsForModule($eventHandler->getModuleName());
		}
		foreach ($this->workflows as &$workflow) {
			switch ($workflow->executionCondition) {
				case \App\Modules\com_vtiger_workflow\VTWorkflowManager::$ON_FIRST_SAVE:
					if ($isNew) {
						$doEvaluate = true;
					} else {
						$doEvaluate = false;
					}
					break;

				case \App\Modules\com_vtiger_workflow\VTWorkflowManager::$ONCE:
					if ($workflow->isCompletedForRecord($recordId)) {
						$doEvaluate = false;
					} else {
						$doEvaluate = true;
					}
					break;

				case \App\Modules\com_vtiger_workflow\VTWorkflowManager::$ON_EVERY_SAVE:
					$doEvaluate = true;
					break;

				case \App\Modules\com_vtiger_workflow\VTWorkflowManager::$ON_MODIFY:
					$doEvaluate = !$isNew && !empty($recordModel->getPreviousValue());
					break;

				case \App\Modules\com_vtiger_workflow\VTWorkflowManager::$MANUAL:
					$doEvaluate = false;
					break;

				case \App\Modules\com_vtiger_workflow\VTWorkflowManager::$ON_SCHEDULE:
					$doEvaluate = false;
					break;

				case \App\Modules\com_vtiger_workflow\VTWorkflowManager::$ON_DELETE:
					$doEvaluate = false;
					break;

				case \App\Modules\com_vtiger_workflow\VTWorkflowManager::$TRIGGER:
					$doEvaluate = false;
					break;

				case \App\Modules\com_vtiger_workflow\VTWorkflowManager::$BLOCK_EDIT:
					$doEvaluate = false;
					break;

				case \App\Modules\com_vtiger_workflow\VTWorkflowManager::$ON_RELATED:
					$doEvaluate = false;
					break;

				default:
					throw new Exception('Should never come here! Execution Condition:' . $workflow->executionCondition);
			}
			if ($doEvaluate && $workflow->evaluate($recordModel, $recordId)) {
				if (\App\Modules\com_vtiger_workflow\VTWorkflowManager::$ONCE == $workflow->executionCondition) {
					$workflow->markAsCompletedForRecord($recordId);
				}
				$workflow->performTasks($recordModel);
			}
		}
	}
}
