<?php

namespace App\Modules\HelpDesk\Handlers;

/**
 * HelpDesk Handler Class
 * @package YetiForce.Handler
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */
class HelpDesk_TicketRangeTime_Handler {

	/**
	 * EntityAfterLink handler function
	 * @param \App\EventHandler $eventHandler
	 */
	public function entityAfterLink(\App\EventHandler $eventHandler)
	{
		$params = $eventHandler->getParams();
		if (in_array($params['destinationModule'], ['Calendar', 'Events', 'Activity', 'ModComments'])) {
			$recordModel = \App\Modules\Base\Models\Record::getInstanceById($params['destinationRecordId'], $params['destinationModule']);
			\App\Modules\HelpDesk\Models\Record::updateTicketRangeTimeField($recordModel, true);
		}
	}

	/**
	 * EntityAfterSave handler function
	 * @param \App\EventHandler $eventHandler
	 */
	public function entityAfterSave(\App\EventHandler $eventHandler)
	{
		$recordModel = $eventHandler->getRecordModel();
		\App\Db::getInstance()->createCommand()->update('vtiger_troubletickets', ['from_portal' => 0], ['ticketid' => $recordModel->getId()])->execute();
		\App\Modules\HelpDesk\Models\Record::updateTicketRangeTimeField($recordModel);
	}
}
