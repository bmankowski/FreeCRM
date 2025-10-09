<?php

namespace FreeCRM\Modules\Vtiger\Handlers;

/**
 * Automatic assignment Handler Class
 * @package YetiForce.Handler
 * @license licenses/License.html
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 */
class AutomaticAssignment {

	/**
	 * EntitySystemAfterCreate handler function
	 * @param App\EventHandler $eventHandler
	 */
	public function entitySystemAfterCreate(App\EventHandler $eventHandler)
	{
		$recordModel = $eventHandler->getRecordModel();
		Settings_AutomaticAssignment_Module_Model::autoAssignExecute($recordModel);
	}
}
