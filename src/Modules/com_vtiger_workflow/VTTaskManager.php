<?php

namespace App\Modules\com_vtiger_workflow;

/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * ********************************************************************************** */

/**
 * Functionality to save and retrieve Tasks from the database.
 */
class VTTaskManager {

	private $adb = null;

	function __construct($adb)
	{
		$this->adb = $adb;
	}

	/**
	 * Save the task into the database.
	 *
	 * When a new task is saved for the first time a field is added to it called
	 * id that stores the task id used in the database.
	 *
	 * @param $summary A summary of the task instance.
	 * @param $task The task instance to save.
	 * @return The id of the task
	 */
	public function saveTask($task)
	{
		$db = \App\Db::getInstance();
		if (is_numeric($task->id)) {//How do I check whether a member exists in php?
			$taskId = $task->id;
			$db->createCommand()->update('com_vtiger_workflowtasks', ['summary' => $task->summary, 'task' => serialize($task)], ['task_id' => $taskId])->execute();
			return $taskId;
		} else {
			$taskId = $db->getUniqueID("com_vtiger_workflowtasks");
			$task->id = $taskId;
			$db->createCommand()->insert('com_vtiger_workflowtasks', [
				'task_id' => $taskId,
				'workflow_id' => $task->workflowId,
				'summary' => $task->summary,
				'task' => serialize($task)
			])->execute();
			return $taskId;
		}
	}

	public function deleteTask($taskId)
	{
		$adb = $this->adb;
		$adb->pquery("delete from com_vtiger_workflowtasks where task_id=?", array($taskId));
	}

	/**
	 * Create a new class instance
	 */
	public function createTask($taskType, $workflowId)
	{
		$taskTypeInstance = VTTaskType::getInstanceFromTaskType($taskType);
		$taskClass = $taskTypeInstance->get('classname');
		$this->requireTask($taskClass, $taskTypeInstance);
		$task = new $taskClass();
		$task->workflowId = $workflowId;
		$task->summary = "";
		$task->active = true;
		return $task;
	}

	/**
	 * Retrieve a task from the database
	 *
	 * @param $taskId The id of the task to retrieve.
	 * @return VTTask The retrieved task.
	 */
	public function retrieveTask($taskId)
	{
		$adb = $this->adb;
		$result = $adb->pquery("select task from com_vtiger_workflowtasks where task_id=?", array($taskId));
		$data = $adb->raw_query_result_rowdata($result, 0);
		$task = $data["task"];
		$task = $this->unserializeTask($task);

		return $task;
	}

	/**
	 *
	 */
	public function getTasksForWorkflow($workflowId)
	{
		if (\App\Cache::staticHas('getTasksForWorkflow', $workflowId)) {
			return \App\Cache::staticGet('getTasksForWorkflow', $workflowId);
		}
		$rows = (new \App\Db\Query())->select(['task'])->from('com_vtiger_workflowtasks')->where(['workflow_id' => $workflowId])->column();
		$tasks = [];
		foreach ($rows as &$task) {
			$this->requireTask(self::taskName($task));
			$tasks[] = unserialize($task);
		}
		\App\Cache::staticGet('getTasksForWorkflow', $workflowId, $tasks);
		return $tasks;
	}

	/**
	 *
	 */
	public function unserializeTask($str)
	{
		$this->requireTask(self::taskName($str));
		return unserialize($str);
	}

	/**
	 *
	 */
	function getTasks()
	{
		$adb = $this->adb;
		$result = $adb->query("select task from com_vtiger_workflowtasks");
		return $this->getTasksForResult($result);
	}

	private function getTasksForResult($result)
	{
		$adb = $this->adb;
		$it = new \App\Events\SqlResultIterator($adb, $result);
		$tasks = array();
		foreach ($it as $row) {
			$text = $row->task;

			$this->requireTask(self::taskName($text));
			$tasks[] = unserialize($text);
		}
		return $tasks;
	}

	private function taskName($serializedTask)
	{
		$matches = [];
		preg_match('/"([^"]+)"/', $serializedTask, $matches);
		return $matches[1];
	}

	private function requireTask($taskType, $taskTypeInstance=null)
	{
		if (!empty($taskTypeInstance)) {
			$taskClassPath = $taskTypeInstance->get('classpath');
			require_once($taskClassPath);
		} else {
			if (!empty($taskType)) {
				require_once("tasks/$taskType.php");
			}
		}
	}

	public function retrieveTemplatePath($moduleName, $taskTypeInstance)
	{
		$taskTemplatePath = $taskTypeInstance->get('templatepath');
		if (!empty($taskTemplatePath)) {
			return $taskTemplatePath;
		} else {
			$taskType = $taskTypeInstance->get('classname');
			return "$moduleName/taskforms/$taskType.tpl";
		}
	}
}
