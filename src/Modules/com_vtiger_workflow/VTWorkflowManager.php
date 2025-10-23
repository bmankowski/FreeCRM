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

class VTWorkflowManager {

	// Commonly used dynamic properties - declared to avoid PHP 8.2+ deprecation warnings
	public $adb;
	
	static $ON_FIRST_SAVE = 1;
	static $ONCE = 2;
	static $ON_EVERY_SAVE = 3;
	static $ON_MODIFY = 4;
	static $ON_DELETE = 5;
	static $ON_SCHEDULE = 6;
	static $MANUAL = 7;
	static $TRIGGER = 8;
	static $BLOCK_EDIT = 9;
	static $ON_RELATED = 10;

	public function __construct($adb = false)
	{
		$this->adb = $adb;
	}

	public function save($workflow)
	{
		if (isset($workflow->id)) {
			$wf = $workflow;
			if ($wf->filtersavedinnew == null)
				$wf->filtersavedinnew = 5;
			\App\Db::getInstance()->createCommand()->update('com_vtiger_workflows', [
				'module_name' => $wf->moduleName,
				'summary' => $wf->description,
				'test' => $wf->test,
				'execution_condition' => $wf->executionCondition,
				'defaultworkflow' => $wf->defaultworkflow,
				'filtersavedinnew' => $wf->filtersavedinnew,
				'schtypeid' => $wf->schtypeid,
				'schtime' => $wf->schtime,
				'schdayofmonth' => $wf->schdayofmonth,
				'schdayofweek' => $wf->schdayofweek,
				'schannualdates' => $wf->schannualdates,
				'nexttrigger_time' => empty($wf->nexttrigger_time) ? null : $wf->nexttrigger_time
				], ['workflow_id' => $wf->id])->execute();
		} else {
			$db = \App\Db::getInstance();
			$wf = $workflow;
			if ($wf->filtersavedinnew == null)
				$wf->filtersavedinnew = 5;
			$db->createCommand()->insert('com_vtiger_workflows', [
				'module_name' => $wf->moduleName,
				'summary' => $wf->description,
				'test' => $wf->test,
				'execution_condition' => $wf->executionCondition,
				'type' => $wf->type,
				'defaultworkflow' => $wf->defaultworkflow,
				'filtersavedinnew' => $wf->filtersavedinnew,
				'schtypeid' => $wf->schtypeid,
				'schtime' => $wf->schtime,
				'schdayofmonth' => $wf->schdayofmonth,
				'schdayofweek' => $wf->schdayofweek,
				'schannualdates' => $wf->schannualdates,
				'nexttrigger_time' => empty($wf->nexttrigger_time) ? null : $wf->nexttrigger_time
			])->execute();
			$wf->id = $db->getLastInsertID('com_vtiger_workflows_workflow_id_seq');
		}
	}

	public function getWorkflows()
	{
		$query = (new \App\Db\Query())
			->select(['workflow_id', 'module_name', 'summary', 'test', 'execution_condition', 'defaultworkflow', 'type', 'filtersavedinnew'])
			->from('com_vtiger_workflows');
		return $this->getWorkflowsForResult($query->all());
	}

	/**
	 * Function returns scheduled workflows
	 * @param DateTime $referenceTime
	 * @return Workflow
	 */
	public function getScheduledWorkflows($referenceTime = false)
	{
		$query = (new \App\Db\Query())->from('com_vtiger_workflows')->where(['execution_condition' => \App\Modules\com_vtiger_workflow\VTWorkflowManager::$ON_SCHEDULE]);
		if ($referenceTime) {
			$query->andWhere(['or', ['nexttrigger_time' => null], ['<=', 'nexttrigger_time', $referenceTime]]);
		}
		return $this->getWorkflowsForResult($query->all());
	}

	/**
	 * Function to get the number of scheduled workflows
	 * @return Integer
	 */
	public function getScheduledWorkflowsCount()
	{
		$adb = $this->adb;
		$query = 'SELECT count(*) AS count FROM com_vtiger_workflows WHERE execution_condition = ?';
		$params = array(\App\Modules\com_vtiger_workflow\VTWorkflowManager::$ON_SCHEDULE);
		$result = $adb->pquery($query, $params);
		return $adb->query_result($result, 0, 'count');
	}

	/**
	 * Function returns the maximum allowed scheduled workflows
	 * @return int
	 */
	public function getMaxAllowedScheduledWorkflows()
	{
		return 10;
	}

	public function getWorkflowsForModule($moduleName, $executionCondition = false)
	{
		if (\App\Cache\Cache::has('WorkflowsForModule', $moduleName)) {
			$rows = \App\Cache\Cache::get('WorkflowsForModule', $moduleName);
		} else {
			$rows = (new \App\Db\Query())
					->select(['workflow_id', 'module_name', 'summary', 'test', 'execution_condition', 'defaultworkflow', 'type', 'filtersavedinnew'])
					->from('com_vtiger_workflows')
					->where(['module_name' => $moduleName])->all();
			\App\Cache\Cache::save('WorkflowsForModule', $moduleName, $rows);
		}
		if ($executionCondition) {
			foreach ($rows as $key => &$row) {
				if ($row['execution_condition'] !== $executionCondition) {
					unset($rows[$key]);
				}
			}
		}
		return $this->getWorkflowsForResult($rows);
	}

	protected function getWorkflowsForResult($rows)
	{
		$workflows = [];
		foreach ($rows as &$row) {
			$workflow = $this->getWorkflowInstance($row['type']);
			$workflow->setup($row);
			if (!is_a($workflow, 'Workflow'))
				continue;

			$workflows[] = $workflow;
		}
		return $workflows;
	}

	protected function getWorkflowInstance($type = 'basic')
	{
		$configReader = new \App\Utils\ConfigReader('src/Modules/com_vtiger_workflow/config.inc', 'workflowConfig');
		$workflowTypeConfig = $configReader->getConfig($type);
		$workflowClassPath = $workflowTypeConfig['classpath'];
		$workflowClass = $workflowTypeConfig['class'];

		require_once $workflowClassPath;
		$workflow = new $workflowClass();
		return $workflow;
	}

	/**
	 * Retrieve a workflow from the database
	 *
	 * Returns null if the workflow doesn't exist.
	 *
	 * @param The id of the workflow
	 * @return A workflow object.
	 */
	public function retrieve($id)
	{
		$data = (new \App\Db\Query())->from('com_vtiger_workflows')->where(['workflow_id' => $id])->one();
		if ($data) {
			$workflow = $this->getWorkflowInstance($data['type']);
			$workflow->setup($data);
			return $workflow;
		} else {
			return null;
		}
	}

	public function delete($id)
	{
		$adb = $this->adb;
		$adb->pquery("DELETE FROM com_vtiger_workflowtasks WHERE workflow_id IN
							(SELECT workflow_id FROM com_vtiger_workflows WHERE workflow_id=? AND (defaultworkflow IS NULL OR defaultworkflow != 1))", array($id));
		$adb->pquery("DELETE FROM com_vtiger_workflows WHERE workflow_id=? AND (defaultworkflow IS NULL OR defaultworkflow != 1)", array($id));
	}

	public function newWorkflow($moduleName)
	{
		$workflow = $this->getWorkflowInstance();
		$workflow->moduleName = $moduleName;
		$workflow->executionCondition = self::$ON_EVERY_SAVE;
		$workflow->type = 'basic';
		return $workflow;
	}

	/**
	 * Export a workflow as a json encoded string
	 *
	 * @param $workflow The workflow instance to export.
	 */
	public function serializeWorkflow($workflow)
	{
		$exp = array();
		$exp['moduleName'] = $workflow->moduleName;
		$exp['description'] = $workflow->description;
		$exp['test'] = $workflow->test;
		$exp['executionCondition'] = $workflow->executionCondition;
		$exp['schtypeid'] = $workflow->schtypeid;
		$exp['schtime'] = $workflow->schtime;
		$exp['schdayofmonth'] = $workflow->schdayofmonth;
		$exp['schdayofweek'] = $workflow->schdayofweek;
		$exp['schannualdates'] = $workflow->schannualdates;
		$exp['tasks'] = array();
		$tm = new VTTaskManager($this->adb);
		$tasks = $tm->getTasksForWorkflow($workflow->id);
		foreach ($tasks as $task) {
			unset($task->id);
			unset($task->workflowId);
			$exp['tasks'][] = serialize($task);
		}
		return \App\Json::encode($exp);
	}

	/**
	 * Import a json encoded string as a workflow object
	 *
	 * @return The Workflow instance representing the imported workflow.
	 */
	public function deserializeWorkflow($str)
	{
		$data = \App\Json::decode($str);
		$workflow = $this->newWorkflow($data['moduleName']);
		$workflow->description = $data['description'];
		$workflow->test = $data['test'];
		$workflow->executionCondition = $data['executionCondition'];
		$workflow->schtypeid = $data['schtypeid'];
		$workflow->schtime = $data['schtime'];
		$workflow->schdayofmonth = $data['schdayofmonth'];
		$workflow->schdayofweek = $data['schdayofweek'];
		$workflow->schannualdates = $data['schannualdates'];
		$this->save($workflow);
		$tm = new VTTaskManager($this->adb);
		$tasks = $data['tasks'];
		foreach ($tasks as $taskStr) {
			$task = $tm->unserializeTask($taskStr);
			$task->workflowId = $workflow->id;
			$tm->saveTask($task);
		}
		return $workflow;
	}

	/**
	 * Update the Next trigger timestamp for a workflow
	 */
	public function updateNexTriggerTime($workflow)
	{
		$nextTriggerTime = $workflow->getNextTriggerTime();
		$workflow->setNextTriggerTime($nextTriggerTime);
	}

	/**
	 * Function to get workflows modules those are supporting comments
	 * @param <String> $moduleName
	 * @return <Array> list of Workflow models
	 */
	public function getWorkflowsForModuleSupportingComments($moduleName)
	{
		if (\App\Cache\Cache::has('WorkflowsForModuleSupportingComments', $moduleName)) {
			return \App\Cache\Cache::get('WorkflowsForModuleSupportingComments', $moduleName);
		}
		$query = (new \App\Db\Query())
			->select(['workflow_id', 'module_name', 'summary', 'test', 'execution_condition', 'defaultworkflow', 'type', 'filtersavedinnew'])
			->from('com_vtiger_workflows')
			->where(['module_name' => $moduleName])
			->andWhere(['like', 'test', '_VT_add_comment']);
		$workflowModels = $this->getWorkflowsForResult($query->all());

		$commentSupportedWorkflowModels = array();
		foreach ($workflowModels as $workflowId => &$workflowModel) {
			$conditions = \App\Json::decode($workflowModel->test);
			if (is_array($conditions)) {
				foreach ($conditions as $key => $conditionInfo) {
					if ($conditionInfo['fieldname'] === '_VT_add_comment') {
						unset($conditions[$key]);
						$workflowModel->test = \App\Json::encode($conditions);
						$commentSupportedWorkflowModels[$workflowId] = $workflowModel;
					}
				}
			}
		}
		\App\Cache\Cache::save('WorkflowsForModuleSupportingComments', $moduleName, $commentSupportedWorkflowModels);
		return $commentSupportedWorkflowModels;
	}
}