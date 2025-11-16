<?php

namespace App\Modules\OpenStreetMap\Handlers;

/**
 * Save geographical coordinates Handler Class
 * @package YetiForce.Handler
 * @license licenses/License.html
 * @author Tomasz Kur <t.kur@yetiforce.com>
 */
class OpenStreetMap_OpenStreetMapHandler_Handler {

	/**
	 * EntityAfterSave handler function
	 * @param \App\Events\EventHandler $eventHandler
	 */
	public function entityAfterSave(\App\Events\EventHandler $eventHandler)
	{
		$fieldAddress = [
			'addresslevel', 'buildingnumber', 'localnumber', 'pobox'
		];
		$typeAddressToUpdate = [];
		$recordModel = $eventHandler->getRecordModel();
		if (!$recordModel->isNew()) {
			$deltaFields = array_keys($recordModel->getPreviousValue());
			foreach ($deltaFields as &$deltaField) {
				if ($recordModel->getPreviousValue($deltaField) !== $recordModel->get($deltaField)) {
					foreach ($fieldAddress as &$field) {
						if (strpos($deltaField, $field) !== false) {
							$typeAddressToUpdate [] = substr($deltaField, -1);
						}
					}
				}
			}
		}
		foreach (['a', 'b', 'c'] as &$typeAddress) {
			if (!$recordModel->isEmpty('addresslevel5' . $typeAddress) && ($recordModel->getEntity()->mode !== 'edit' || in_array($typeAddress, $typeAddressToUpdate))) {
				$isCoordinateExists = (new \App\Db\Query())
					->from('u_#__openstreetmap_record_updater')
					->where(['type' => $typeAddress, 'crmid' => $recordModel->getId()])
					->exists();
				$coordinatesModel = \App\Modules\OpenStreetMap\Models\Coordinate::getInstance();
				$address = $coordinatesModel->getUrlParamsToSearching($recordModel, $typeAddress);
				if (!$isCoordinateExists) {
					\App\Db\Db::getInstance()->createCommand()->insert('u_#__openstreetmap_record_updater', [
						'crmid' => $recordModel->getId(),
						'type' => $typeAddress,
						'address' => \App\Utils\Json::encode($address)
					])->execute();
				} else {
					\App\Db\Db::getInstance()->createCommand()
						->update('u_#__openstreetmap_record_updater', ['address' => \App\Utils\Json::encode($address)], ['crmid' => $recordModel->getId(), 'type' => $typeAddress])
						->execute();
				}
			}
		}
	}
}
