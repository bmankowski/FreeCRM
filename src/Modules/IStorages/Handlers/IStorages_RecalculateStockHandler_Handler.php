<?php

namespace FreeCRM\Modules\IStorages\Handlers;

/**
 * RecalculateStock Handler Class
 * @package YetiForce.Handler
 * @license licenses/License.html
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 */
class IStorages_RecalculateStockHandler_Handler {

	/**
	 * EntityAfterSave handler function
	 * @param \App\EventHandler $eventHandler
	 */
	public function entityAfterSave(\App\EventHandler $eventHandler)
	{
		$moduleName = $eventHandler->getModuleName();
		$correctionModules = ['IGRNC' => 'igrnid', 'IGDNC' => 'igdnid'];
		$recordModel = $eventHandler->getRecordModel();
		$status = strtolower($moduleName) . '_status';
		// Checks if the module is a correction module
		if (isset($correctionModules[$moduleName])) {
			$relatedModuleField = $correctionModules[$moduleName];
			$relatedModuleRecordId = $recordModel->get($relatedModuleField);
			$relatedModuleRecordModel = \FreeCRM\Modules\Vtiger\Models\Record::getInstanceById($relatedModuleRecordId);
		}
		if ($recordModel->get($status) === 'PLL_ACCEPTED') {
			if (isset($correctionModules[$moduleName])) {
				$this->getInventoryDataAndSend($relatedModuleRecordModel, 'remove');
			}
			$this->getInventoryDataAndSend($recordModel, 'add');
		} else {
			$delta = $recordModel->getPreviousValue($status);
			if ($delta && 'PLL_ACCEPTED' === $delta) {
				if (isset($correctionModules[$moduleName])) {
					$this->getInventoryDataAndSend($relatedModuleRecordModel, 'add');
				}
				$this->getInventoryDataAndSend($recordModel, 'remove');
			}
		}
	}

	public function getInventoryDataAndSend(\FreeCRM\Modules\Vtiger\Models\Record $recordModel, $action)
	{
		$moduleName = $recordModel->getModuleName();
		$inventoryData = $recordModel->getInventoryData();
		if (!empty($inventoryData) && $recordModel->get('storageid')) {
			\FreeCRM\Modules\IStorages\Models\Module::RecalculateStock($moduleName, $inventoryData, $recordModel->get('storageid'), $action);
		}
	}
}
