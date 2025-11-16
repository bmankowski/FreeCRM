<?php

namespace App\Modules\Base\Handlers;

/**
 * Abstract base handler class
 * @package YetiForce.Handler
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */
class Vtiger_RecordLabelUpdater_Handler {

	/**
	 * EntityAfterSave function
	 * @param \App\Events\EventHandler $eventHandler
	 */
	public function entityAfterSave(\App\Events\EventHandler $eventHandler)
	{
		\App\Records\Record::updateLabelOnSave($eventHandler->getRecordModel());
	}
}
