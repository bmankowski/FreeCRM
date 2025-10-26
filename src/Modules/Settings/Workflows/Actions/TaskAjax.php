<?php

namespace App\Modules\Settings\Workflows\Actions;
use App\Modules\Settings\WorkflowsModels\TaskRecord;


/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * *********************************************************************************** */


class TaskAjax extends \App\Modules\Settings\Base\Views\IndexAjax
{

	public function __construct()
	{
		parent::__construct();
		$this->exposeMethod('Delete');
		$this->exposeMethod('ChangeStatus');
		$this->exposeMethod('ChangeStatusAllTasks');
		$this->exposeMethod('Save');
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$mode = $request->getMode();
		if (!empty($mode)) {
			$this->invokeExposedMethod($mode, $request);
			return;
		}
	}

	public function Delete(\App\Http\Vtiger_Request $request)
	{
		$record = $request->get('task_id');
		if (!empty($record)) {
			$taskRecordModel = \App\Modules\Settings\Workflows\Models\TaskRecord::getInstance($record);
			$taskRecordModel->delete();
			$response = new \App\Http\Vtiger_Response();
			$response->setResult(array('ok'));
			$response->emit();
		}
	}

	public function ChangeStatus(\App\Http\Vtiger_Request $request)
	{
		$record = $request->get('task_id');
		if (!empty($record)) {
			$taskRecordModel = \App\Modules\Settings\Workflows\Models\TaskRecord::getInstance($record);
			$taskObject = $taskRecordModel->getTaskObject();
			if ($request->get('status') == 'true')
				$taskObject->active = true;
			else
				$taskObject->active = false;
			$taskRecordModel->save();
			$response = new \App\Http\Vtiger_Response();
			$response->setResult(array('ok'));
			$response->emit();
		}
	}

	public function ChangeStatusAllTasks(\App\Http\Vtiger_Request $request)
	{
		$record = $request->get('record');
		$status = $request->get('status');
		if (!empty($record)) {
			$workflowModel = \App\Modules\Settings\Workflows\Models\Record::getInstance($record);
			$taskList = $workflowModel->getTasks();
			foreach ($taskList as $task) {
				$taskRecordModel = \App\Modules\Settings\Workflows\Models\TaskRecord::getInstance($task->getId());
				$taskObject = $taskRecordModel->getTaskObject();
				if ($status == 'true')
					$taskObject->active = true;
				else
					$taskObject->active = false;
				$taskRecordModel->save();
			}
			$response = new \App\Http\Vtiger_Response();
			$response->setResult(array('success' => true, 'count' => count($taskList)));
			$response->emit();
		}
	}

	public function Save(\App\Http\Vtiger_Request $request)
	{

		$workflowId = $request->get('for_workflow');
		if (!empty($workflowId)) {
			$record = $request->get('task_id');
			if ($record) {
				$taskRecordModel = \App\Modules\Settings\Workflows\Models\TaskRecord::getInstance($record);
			} else {
				$workflowModel = \App\Modules\Settings\Workflows\Models\Record::getInstance($workflowId);
				$taskRecordModel = \App\Modules\Settings\Workflows\Models\TaskRecord::getCleanInstance($workflowModel, $request->get('taskType'));
			}

			$taskObject = $taskRecordModel->getTaskObject();
			$taskObject->summary = htmlspecialchars($request->get("summary"));
			$active = $request->get("active");
			if ($active == "true") {
				$taskObject->active = true;
			} else if ($active == "false") {
				$taskObject->active = false;
			}
			$checkSelectDate = $request->get('check_select_date');

			if (!empty($checkSelectDate)) {
				$trigger = array(
					'days' => ($request->get('select_date_direction') == 'after' ? 1 : -1) * (int) $request->get('select_date_days'),
					'field' => $request->get('select_date_field')
				);
				$taskObject->trigger = $trigger;
			} else {
				$taskObject->trigger = null;
			}

			$fieldNames = $taskObject->getFieldNames();

			foreach ($fieldNames as $fieldName) {
				if ($fieldName == 'field_value_mapping' || $fieldName == 'content') {
					$values = \App\Json::decode($request->getRaw($fieldName));

					if ($values) {
						foreach ($values as $index => $value) {
							$values[$index]['value'] = htmlspecialchars($value['value']);
						}

						$taskObject->$fieldName = \App\Json::encode($values);
					} else {
						$taskObject->$fieldName = $request->getRaw($fieldName);
					}
				} else {
					$taskObject->$fieldName = $request->get($fieldName);
				}
			}

			$taskType = get_class($taskObject);
			if ($taskType === 'VTCreateEntityTask' && $taskObject->field_value_mapping) {
				$relationModuleModel = \App\Modules\Base\Models\Module::getInstance($taskObject->entity_type);
				$ownerFieldModels = $relationModuleModel->getFieldsByType('owner');

				$fieldMapping = \App\Json::decode($taskObject->field_value_mapping);
				foreach ($fieldMapping as $key => $mappingInfo) {
					if (array_key_exists($mappingInfo['fieldname'], $ownerFieldModels)) {
						if ($mappingInfo['value'] == 'assigned_user_id') {
							$fieldMapping[$key]['valuetype'] = 'fieldname';
						} else {
							$userRecordModel = \App\Modules\Users\Models\Record::getInstanceById($mappingInfo['value'], 'Users');
							$ownerName = $userRecordModel->get('user_name');

							if (!$ownerName) {
								$groupRecordModel = \App\Modules\Settings\Groups\Models\Record::getInstance($mappingInfo['value']);
								$ownerName = $groupRecordModel->getName();
							}
							$fieldMapping[$key]['value'] = $ownerName;
						}
					}
				}
				$taskObject->field_value_mapping = \App\Json::encode($fieldMapping);
			}

			$taskRecordModel->save();
			$response = new \App\Http\Vtiger_Response();
			$response->setResult(array('for_workflow' => $workflowId));
			$response->emit();
		}
	}

	public function validateRequest(\App\Http\Vtiger_Request $request)
	{
		$request->validateWriteAccess();
	}
}
