<?php

namespace App\Modules\OSSTimeControl\Handlers;

/**
 * Time Control Handler Class
 * @package YetiForce.Handler
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 */

require_once ROOT_DIRECTORY . '/include/Webservices/Utils.php';
require_once ROOT_DIRECTORY . '/include/Webservices/Retrieve.php';

class OSSTimeControl_TimeControl_Handler {

	/**
	 * EntityAfterUnLink handler function
	 * @param \App\Events\EventHandler $eventHandler
	 */
	public function entityAfterUnLink(\App\Events\EventHandler $eventHandler)
	{
		$params = $eventHandler->getParams();
		$wfs = new \App\Modules\Workflow\VTWorkflowManager();
		$workflows = $wfs->getWorkflowsForModule($params['destinationModule'], \App\Modules\Workflow\VTWorkflowManager::$MANUAL);
		$recordModel = \App\Modules\Base\Models\Record::getInstanceById($params['destinationRecordId'], $params['destinationModule']);
		foreach ($workflows as &$workflow) {
			if ($workflow->evaluate($recordModel)) {
				$workflow->performTasks($recordModel);
			}
		}
	}

	/**
	 * EntityAfterDelete handler function
	 * @param \App\Events\EventHandler $eventHandler
	 */
	public function entityAfterDelete(\App\Events\EventHandler $eventHandler)
	{
		$recordModel = $eventHandler->getRecordModel();
		$wfs = new \App\Modules\Workflow\VTWorkflowManager();
		$workflows = $wfs->getWorkflowsForModule($eventHandler->getModuleName(), \App\Modules\Workflow\VTWorkflowManager::$MANUAL);
		foreach ($workflows as &$workflow) {
			if ($workflow->evaluate($recordModel)) {
				$workflow->performTasks($recordModel);
			}
		}
	}

	/**
	 * EntityAfterSave handler function
	 * @param \App\Events\EventHandler $eventHandler
	 */
	public function entityAfterSave(\App\Events\EventHandler $eventHandler)
	{
		$recordModel = $eventHandler->getRecordModel();
		\App\Modules\OSSTimeControl\Models\Record::setSumTime($recordModel);
		$wfs = new \App\Modules\Workflow\VTWorkflowManager();
		$workflows = $wfs->getWorkflowsForModule($eventHandler->getModuleName(), \App\Modules\Workflow\VTWorkflowManager::$MANUAL);
		foreach ($workflows as &$workflow) {
			if ($workflow->evaluate($recordModel)) {
				$workflow->performTasks($recordModel);
			}
		}
	}

	/**
	 * EntityAfterRestore handler function
	 * @param \App\Events\EventHandler $eventHandler
	 */
	public function entityAfterRestore(\App\Events\EventHandler $eventHandler)
	{
		$recordModel = $eventHandler->getRecordModel();
		$wfs = new \App\Modules\Workflow\VTWorkflowManager();
		$workflows = $wfs->getWorkflowsForModule($eventHandler->getModuleName(), \App\Modules\Workflow\VTWorkflowManager::$MANUAL);
		foreach ($workflows as &$workflow) {
			if ($workflow->evaluate($recordModel)) {
				$workflow->performTasks($recordModel);
			}
		}
	}
}
