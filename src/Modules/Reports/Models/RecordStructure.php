<?php

namespace App\Modules\Reports\Models;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

/**
 * Vtiger Edit View Record Structure Model
 */
class RecordStructure extends \App\Runtime\BaseModel
{

	protected $moduleName = false;

	/**
	 * Function to get the values in stuctured format
	 * @return <array> - values in structure array('block'=>array(fieldinfo));
	 */
	public function getStructure()
	{
		$moduleName = $this->moduleName;
		if (!empty($this->structuredValues[$moduleName])) {
			return $this->structuredValues[$moduleName];
		}
		$moduleModel = \App\Modules\Vtiger\Models\Module::getInstance($moduleName);
		if ($moduleName === 'Calendar') {
			$recordStructureInstance = \App\Modules\Vtiger\Models\RecordStructure::getInstanceForModule($moduleModel);
			$moduleRecordStructure = array();
			$calendarRecordStructure = $recordStructureInstance->getStructure();

			$eventsModel = \App\Modules\Vtiger\Models\Module::getInstance('Events');
			$recordStructureInstance = \App\Modules\Vtiger\Models\RecordStructure::getInstanceForModule($eventsModel);
			$eventRecordStructure = $recordStructureInstance->getStructure();

			$blockLabel = 'LBL_CUSTOM_INFORMATION';
			if ($eventRecordStructure[$blockLabel]) {
				if ($calendarRecordStructure[$blockLabel]) {
					$calendarRecordStructure[$blockLabel] = array_merge($calendarRecordStructure[$blockLabel], $eventRecordStructure[$blockLabel]);
				} else {
					$calendarRecordStructure[$blockLabel] = $eventRecordStructure[$blockLabel];
				}
			}
			$moduleRecordStructure = $calendarRecordStructure;
		} else {
			$recordStructureInstance = \App\Modules\Vtiger\Models\RecordStructure::getInstanceForModule($moduleModel);
			$moduleRecordStructure = $recordStructureInstance->getStructure();
		}
		$this->structuredValues[$moduleName] = $moduleRecordStructure;
		return $moduleRecordStructure;
	}

	/**
	 * Function returns the Primary Module Record Structure
	 * @return <\App\Modules\Vtiger\Models\RecordStructure>
	 */
	public function getPrimaryModuleRecordStructure()
	{
		$this->moduleName = $this->getRecord()->getPrimaryModule();
		$primaryModuleRecordStructure = $this->getStructure();
		return $primaryModuleRecordStructure;
	}

	/**
	 * Function returns the Secondary Modules Record Structure
	 * @return <Array of \App\Modules\Vtiger\Models\RecordSructures>
	 */
	public function getSecondaryModuleRecordStructure()
	{
		$recordStructureInstances = array();

		$secondaryModule = $this->getRecord()->getSecondaryModules();
		if (!empty($secondaryModule)) {
			$moduleList = explode(':', $secondaryModule);

			foreach ($moduleList as $moduleName) {
				if (!empty($moduleName)) {
					$this->moduleName = $moduleName;
					$recordStructureInstances[$moduleName] = $this->getStructure();
				}
			}
		}
		return $recordStructureInstances;
	}
}
