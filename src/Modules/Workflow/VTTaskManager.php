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

/**
 * Functionality to save and retrieve Tasks from the database.
 */
class VTTaskManager
{
	private $adb = null;

	public function __construct($adb)
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
		$db = \App\Db\Db::getInstance();
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
	public function createTask(string $taskType, int $workflowId)
	{
		$taskTypeInstance = VTTaskType::getInstanceFromTaskType($taskType);
		$taskClass = $taskTypeInstance->get('classname');
		// Map old class names to new namespaced ones
		$taskClass = $this->resolveTaskClass($taskClass);
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
		$taskData = (new \App\Db\Query())
			->select(['task'])
			->from('com_vtiger_workflowtasks')
			->where(['task_id' => $taskId])
			->scalar();
		if ($taskData === false || $taskData === null || $taskData === '') {
			return null;
		}
		return $this->unserializeTask($taskData);
	}

	/**
	 * Get all tasks for a workflow
	 */
	public function getTasksForWorkflow(int $workflowId): array
	{
		if (\App\Cache\Cache::has('getTasksForWorkflow', $workflowId)) {
			return \App\Cache\Cache::get('getTasksForWorkflow', $workflowId);
		}
		$rows = (new \App\Db\Query())->select(['task'])->from('com_vtiger_workflowtasks')->where(['workflow_id' => $workflowId])->column();
		$tasks = [];
		foreach ($rows as &$task) {
			$tasks[] = $this->unserializeTask($task);
		}
		\App\Cache\Cache::get('getTasksForWorkflow', $workflowId, $tasks);
		return $tasks;
	}

	/**
	 * Unserialize task with backward compatibility for old class names
	 */
	public function unserializeTask(string $str)
	{
		// Handle old serialized class names by registering class aliases
		$this->registerTaskAliases($str);
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

	private function getTasksForResult($result): array
	{
		$adb = $this->adb;
		$it = new \App\Events\SqlResultIterator($adb, $result);
		$tasks = [];
		foreach ($it as $row) {
			$text = $row->task;
			$tasks[] = $this->unserializeTask($text);
		}
		return $tasks;
	}

	/**
	 * Extract class name from serialized task
	 */
	private function taskName(string $serializedTask): string
	{
		$matches = [];
		preg_match('/"([^"]+)"/', $serializedTask, $matches);
		return $matches[1] ?? '';
	}

	/**
	 * Resolve task class name with backward compatibility
	 */
	private function resolveTaskClass(string $taskClass): string
	{
		// If already namespaced, return as is
		if (strpos($taskClass, '\\') !== false) {
			return $taskClass;
		}
		
		// Map old class names to new namespaced ones
		return "\App\\Modules\\Workflow\\Tasks\\{$taskClass}";
	}

	/**
	 * Register class aliases for backward compatibility with old serialized data
	 */
	private function registerTaskAliases(string $serializedTask): void
	{
		$oldClassName = $this->taskName($serializedTask);
		if (empty($oldClassName) || strpos($oldClassName, '\\') !== false) {
			return; // Already namespaced or invalid
		}

		$newClassName = $this->resolveTaskClass($oldClassName);
		
		// Register alias only if not already registered and new class exists
		if (!class_exists($oldClassName, false) && class_exists($newClassName, true)) {
			class_alias($newClassName, $oldClassName);
		}
	}

	public function retrieveTemplatePath(string $moduleName, VTTaskType $taskTypeInstance): string
	{
		$taskTemplatePath = $taskTypeInstance->get('templatepath');
		if (!empty($taskTemplatePath)) {
			return $taskTemplatePath;
		}
		$taskType = $taskTypeInstance->get('classname');
		return "$moduleName/taskforms/$taskType.tpl";
	}
}
