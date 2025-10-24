<?php

namespace App\Modules\Settings\Workflows\Models;


/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

/*
 * Workflow Task Type Model Class
 */
require_once ROOT_DIRECTORY . '/Modules/com_vtiger_workflow/VTTaskManager.php';

class TaskType extends \App\Modules\Vtiger\Models\Record
{

	public function getId()
	{
		return $this->get('id');
	}

	public function getName()
	{
		return $this->get('tasktypename');
	}

	public function getLabel()
	{
		return $this->get('label');
	}

	public function getTemplatePath()
	{
		$templatePath = vtemplate_path('Tasks/' . $this->getName() . '.tpl', 'Settings:Workflows');
		return $templatePath;
	}

	public function getEditViewUrl()
	{
		return '?module=Workflows&parent=Settings&view=EditTask&type=' . $this->getName();
	}

	public static function getInstanceFromClassName($taskClass)
	{
		$db = \App\Database\PearDatabase::getInstance();
		$result = $db->pquery("SELECT * FROM com_vtiger_workflow_tasktypes where classname=?", array($taskClass));
		$row = $db->query_result_rowdata($result, 0);
		$taskTypeObject = VTTaskType::getInstance($row);
		return self::getInstanceFromTaskTypeObject($taskTypeObject);
	}

	public static function getAllForModule($moduleModel)
	{
		$taskTypes = VTTaskType::getAll($moduleModel->getName());
		$taskTypeModels = array();
		foreach ($taskTypes as $taskTypeObject) {
			$taskTypeModels[] = self::getInstanceFromTaskTypeObject($taskTypeObject);
		}
		return $taskTypeModels;
	}

	public static function getInstance($taskType)
	{
		$taskTypeObject = VTTaskType::getInstanceFromTaskType($taskType);
		return self::getInstanceFromTaskTypeObject($taskTypeObject);
	}

	public static function getInstanceFromTaskTypeObject($taskTypeObject)
	{
		return new self($taskTypeObject->data);
	}

	public function getTaskBaseModule()
	{
		$taskTypeName = $this->get('tasktypename');
		switch ($taskTypeName) {
			case 'VTCreateTodoTask' : return \App\Modules\Vtiger\Models\Module::getInstance('Calendar');
			case 'VTCreateEventTask' : return \App\Modules\Vtiger\Models\Module::getInstance('Events');
		}
	}
}
