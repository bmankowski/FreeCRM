<?php

namespace App\Modules\Workflow;

/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * ********************************************************************************** */

class VTTaskType
{

	var $data;

	public function get($key)
	{
		return $this->data[$key];
	}

	public function set($key, $value)
	{
		$this->data[$key] = $value;
		return $this;
	}

	public function setData($valueMap)
	{
		$this->data = $valueMap;
		return $this;
	}

	public static function getInstance($values)
	{
		$instance = new self();
		return $instance->setData($values);
	}

	public static function registerTaskType($taskType)
	{
		$adb = \App\Database\PearDatabase::getInstance();
		$modules = \App\Json::encode($taskType['modules']);
		$taskTypeId = $adb->getUniqueID('com_vtiger_workflow_tasktypes');
		$taskType['id'] = $taskTypeId;
		$adb->pquery("INSERT INTO com_vtiger_workflow_tasktypes
									(id, tasktypename, label, classname, classpath, templatepath, modules, sourcemodule)
									values (?,?,?,?,?,?,?,?)", array($taskTypeId, $taskType['name'], $taskType['label'], $taskType['classname'], $taskType['classpath'], $taskType['templatepath'], $modules, $taskType['sourcemodule']));
	}

	public static function getAll($moduleName = '')
	{
		$adb = \App\Database\PearDatabase::getInstance();

		$result = $adb->pquery("SELECT * FROM com_vtiger_workflow_tasktypes", array());
		$numrows = $adb->num_rows($result);
		for ($i = 0; $i < $numrows; $i++) {
			$rawData = $adb->raw_query_result_rowdata($result, $i);
			$taskName = $rawData['tasktypename'];
			$moduleslist = $rawData['modules'];
			$sourceModule = $rawData['sourcemodule'];
			$modules = \App\Json::decode($moduleslist);
			$includeModules = $modules['include'];
			$excludeModules = $modules['exclude'];

			if (!empty($sourceModule)) {
				if (\App\Utils\ModuleUtils::getModuleId($sourceModule) == null || !\App\Utils\ModuleUtils::isModuleActive($sourceModule)) {
					continue;
				}
			}

			if (empty($includeModules) && empty($excludeModules)) {
				$taskTypeInstances[$taskName] = self::getInstance($rawData);
				continue;
			} elseif (!empty($includeModules)) {
				if (in_array($moduleName, $includeModules)) {
					$taskTypeInstances[$taskName] = self::getInstance($rawData);
				}
				continue;
			} elseif (!empty($excludeModules)) {
				if (!(in_array($moduleName, $excludeModules))) {
					$taskTypeInstances[$taskName] = self::getInstance($rawData);
				}
				continue;
			}
		}
		return $taskTypeInstances;
	}

	public static function getInstanceFromTaskType($taskType)
	{
		$adb = \App\Database\PearDatabase::getInstance();

		$result = $adb->pquery("SELECT * FROM com_vtiger_workflow_tasktypes where tasktypename=?", array($taskType));
		$taskTypes['name'] = $adb->query_result($result, 0, 'tasktypename');
		$taskTypes['label'] = $adb->query_result($result, 0, 'label');
		$taskTypes['classname'] = $adb->query_result($result, 0, 'classname');
		$taskTypes['classpath'] = $adb->query_result($result, 0, 'classpath');
		$taskTypes['templatepath'] = $adb->query_result($result, 0, 'templatepath');
		$taskTypes['sourcemodule'] = $adb->query_result($result, 0, 'sourcemodule');

		$taskDetails = self::getInstance($taskTypes);
		return $taskDetails;
	}
}

