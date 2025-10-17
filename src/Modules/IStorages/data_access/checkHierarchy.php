<?php

namespace App\Modules\IStorages;

/**
 * Prevents setting loop of parent storages
 * @package YetiForce.DataAccess
 * @license licenses/License.html
 * @author Krzysztof Gastołek <krzysztof.gastolek@wars.pl>
 */
class checkHierarchy {

	public $config = false;

	public function process($moduleName, $id, $recordData, $config)
	{
		$parentId = $recordData['parentid'];
		$focus = \App\CRMEntity::getInstance($moduleName);
		$storages = [];

		if ($id > 0) {
			$children = $focus->getChildIStorages($id, $storages, 0);
		} else {
			$children = [];
		}

		$saveRecord = $this->checkChildren($parentId, $children);

		if ($saveRecord === true) {
			return ['save_record' => true];
		} else {
			return [
				'save_record' => false,
				'type' => 0,
				'info' => [
					'title' => \App\Runtime\Vtiger_Language_Handler::translate('LBL_FAILED_TO_APPROVE_CHANGES', 'Settings:DataAccess'),
					'text' => \App\Runtime\Vtiger_Language_Handler::translate('LBL_PARENT_IS_CHILD', $moduleName),
					'type' => 'error'
				]
			];
		}
	}

	/**
	 * Checks if chosen parent is record child
	 * @param type $parentId
	 * @param type $childArray
	 * @return true if parent can be set
	 */
	public function checkChildren($parentId, $childArray)
	{
		foreach ($childArray as $key => $value) {
			if (is_int($key) && is_array($value)) {
				if ($key == $parentId) {
					return false;
				} else if (!$this->checkChildren($parentId, $value)) {
					return false;
				}
			}
		}
		return true;
	}

	public function getConfig($id, $module, $baseModule)
	{
		return false;
	}
}
