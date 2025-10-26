<?php

namespace App\Modules\Base\Handlers;

/**
 * Multi Reference Updater Handler Class
 * @package YetiForce.Handler
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 */
class Vtiger_MultiReferenceUpdater_Handler {

	/**
	 * EntityAfterLink handler function
	 * @param \App\EventHandler $eventHandler
	 */
	public function entityAfterLink(\App\EventHandler $eventHandler)
	{
		$params = $eventHandler->getParams();
		$fields = \App\Modules\Base\UiTypes\MultiReferenceValue::getFieldsByModules($params['sourceModule'], $params['destinationModule']);
		foreach ($fields as &$field) {
			$fieldModel = new \App\Modules\Base\Models\Field();
			$fieldModel->initialize($field);
			$uitypeModel = $fieldModel->getUITypeModel();
			$uitypeModel->addValue($params['CRMEntity'], $params['sourceRecordId'], $params['destinationRecordId']);
		}
	}

	/**
	 * EntityAfterUnLink handler function
	 * @param \App\EventHandler $eventHandler
	 */
	public function entityAfterUnLink(\App\EventHandler $eventHandler)
	{
		$params = $eventHandler->getParams();
		$fields = \App\Modules\Base\UiTypes\MultiReferenceValue::getFieldsByModules($params['sourceModule'], $params['destinationModule']);
		foreach ($fields as &$field) {
			$fieldModel = new \App\Modules\Base\Models\Field();
			$fieldModel->initialize($field);
			$uitypeModel = $fieldModel->getUITypeModel();
			$uitypeModel->reloadValue($params['sourceModule'], $params['sourceRecordId']);
		}
	}

	/**
	 * EntityAfterSave function
	 * @param \App\EventHandler $eventHandler
	 */
	public function entityAfterSave(\App\EventHandler $eventHandler)
	{
		$recordModel = $eventHandler->getRecordModel();
		$moduleName = $eventHandler->getModuleName();
		$moduleIds = \App\Modules\Base\UiTypes\MultiReferenceValue::getMultiReferenceModules($moduleName);
		if ($moduleIds) {
			$previousValue = $recordModel->getPreviousValue();
			$referenceFields = $recordModel->getModule()->getFieldsByReference();
			foreach ($referenceFields as $fieldName => $fieldModel) {
				if (isset($previousValue[$fieldName]) && !$recordModel->isNew()) {
					$module = \App\Record::getType($previousValue[$fieldName]);
					if ($module && in_array(\vtlib\Functions::getModuleId($module), $moduleIds)) {
						\App\Modules\Base\UiTypes\MultiReferenceValue::setRecordToCron($module, $moduleName, $previousValue[$fieldName]);
					}
				}
				$module = \App\Record::getType($recordModel->get($fieldName));
				if ($module && in_array(\vtlib\Functions::getModuleId($module), $moduleIds)) {
					\App\Modules\Base\UiTypes\MultiReferenceValue::setRecordToCron($module, $moduleName, $recordModel->get($fieldName));
				}
			}
		}
	}
}
