<?php

namespace App\Modules\OSSTimeControl\Handlers;

/**
 * Time Control Handler Class
 * @package YetiForce.Handler
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 */

require_once ROOT_DIRECTORY . '/src/Modules/com_vtiger_workflow/include.php';
require_once ROOT_DIRECTORY . '/src/Modules/com_vtiger_workflow/VTEntityCache.php';
require_once ROOT_DIRECTORY . '/include/Webservices/Utils.php';
require_once ROOT_DIRECTORY . '/include/Webservices/Retrieve.php';

class OSSTimeControl_TimeControl_Handler {

	/**
	 * EntityAfterUnLink handler function
	 * @param \App\EventHandler $eventHandler
	 */
	public function entityAfterUnLink(\App\EventHandler $eventHandler)
	{
		$params = $eventHandler->getParams();
		$wfs = new \App\Modules\com_vtiger_workflow\VTWorkflowManager();
		$workflows = $wfs->getWorkflowsForModule($params['destinationModule'], \App\Modules\com_vtiger_workflow\VTWorkflowManager::$MANUAL);
		$recordModel = \App\Modules\Vtiger\Models\Record::getInstanceById($params['destinationRecordId'], $params['destinationModule']);
		foreach ($workflows as &$workflow) {
			if ($workflow->evaluate($recordModel)) {
				$workflow->performTasks($recordModel);
			}
		}
	}

	/**
	 * EntityAfterDelete handler function
	 * @param \App\EventHandler $eventHandler
	 */
	public function entityAfterDelete(\App\EventHandler $eventHandler)
	{
		$recordModel = $eventHandler->getRecordModel();
		$wfs = new \App\Modules\com_vtiger_workflow\VTWorkflowManager();
		$workflows = $wfs->getWorkflowsForModule($eventHandler->getModuleName(), \App\Modules\com_vtiger_workflow\VTWorkflowManager::$MANUAL);
		foreach ($workflows as &$workflow) {
			if ($workflow->evaluate($recordModel)) {
				$workflow->performTasks($recordModel);
			}
		}
	}

	/**
	 * EntityAfterSave handler function
	 * @param \App\EventHandler $eventHandler
	 */
	public function entityAfterSave(\App\EventHandler $eventHandler)
	{
		$recordModel = $eventHandler->getRecordModel();
		\App\Modules\OSSTimeControl\Models\Record::setSumTime($recordModel);
		$wfs = new \App\Modules\com_vtiger_workflow\VTWorkflowManager();
		$workflows = $wfs->getWorkflowsForModule($eventHandler->getModuleName(), \App\Modules\com_vtiger_workflow\VTWorkflowManager::$MANUAL);
		foreach ($workflows as &$workflow) {
			if ($workflow->evaluate($recordModel)) {
				$workflow->performTasks($recordModel);
			}
		}
	}

	/**
	 * EntityAfterRestore handler function
	 * @param \App\EventHandler $eventHandler
	 */
	public function entityAfterRestore(\App\EventHandler $eventHandler)
	{
		$recordModel = $eventHandler->getRecordModel();
		$wfs = new \App\Modules\com_vtiger_workflow\VTWorkflowManager();
		$workflows = $wfs->getWorkflowsForModule($eventHandler->getModuleName(), \App\Modules\com_vtiger_workflow\VTWorkflowManager::$MANUAL);
		foreach ($workflows as &$workflow) {
			if ($workflow->evaluate($recordModel)) {
				$workflow->performTasks($recordModel);
			}
		}
	}
}
