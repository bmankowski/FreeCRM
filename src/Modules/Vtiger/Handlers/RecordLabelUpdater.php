<?php

namespace FreeCRM\Modules\Vtiger\Handlers;

/**
 * Abstract base handler class
 * @package YetiForce.Handler
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */
class RecordLabelUpdater {

	/**
	 * EntityAfterSave function
	 * @param \App\EventHandler $eventHandler
	 */
	public function entityAfterSave(\App\EventHandler $eventHandler)
	{
		\App\Record::updateLabelOnSave($eventHandler->getRecordModel());
	}
}
