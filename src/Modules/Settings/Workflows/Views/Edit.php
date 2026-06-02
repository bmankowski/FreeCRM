<?php

namespace App\Modules\Settings\Workflows\Views;
use App\Modules\Settings\WorkflowsModels\TaskType;
use App\Modules\Settings\WorkflowsModels\RecordStructure;


/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * ********************************************************************************** */


class Edit extends \App\Modules\Settings\Base\Views\Index
{

	public function process(\App\Http\Vtiger_Request $request)
	{
		$mode = $request->getMode();
		if ($mode) {
			$this->$mode($request);
		} else {
			$this->step1($request);
		}
	}

	public function preProcess(\App\Http\Vtiger_Request $request, $display = true)
	{
		parent::preProcess($request, false);
		$viewer = $this->getViewer($request);

		$recordId = $request->get('record');
		$viewer->assign('RECORDID', $recordId);
		if ($recordId) {
			$workflowModel = \App\Modules\Settings\Workflows\Models\Record::getInstance($recordId);
			$viewer->assign('WORKFLOW_MODEL', $workflowModel);
		}
		$viewer->assign('RECORD_MODE', $request->getMode());
	}

	public function step1(\App\Http\Vtiger_Request $request)
	{
		$currentUser = $request->getUser();
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		$qualifiedModuleName = $request->getModule(false);
		$weekDays = ['Sunday' => 0, 'Monday' => 1, 'Tuesday' => 2, 'Wednesday' => 3, 'Thursday' => 4, 'Friday' => 5, 'Saturday' => 6];

		$recordId = $request->get('record');
		if ($recordId) {
			$workflowModel = \App\Modules\Settings\Workflows\Models\Record::getInstance($recordId);
			$viewer->assign('RECORDID', $recordId);
			$viewer->assign('MODULE_MODEL', $workflowModel->getModule());
			$viewer->assign('MODE', 'edit');
		} else {
			$workflowModel = \App\Modules\Settings\Workflows\Models\Record::getCleanInstance($moduleName);
			$viewer->assign('RECORDID', '');
			$viewer->assign('MODE', 'create');
			$selectedModule = $request->get('source_module');
			if (!empty($selectedModule)) {
				$viewer->assign('SELECTED_MODULE', $selectedModule);
			}
			$executionCondition = (int) $request->get('execution_condition');
			$validTriggers = array_keys(\App\Modules\Settings\Workflows\Models\Module::getTriggerTypes());
			if ($executionCondition > 0 && \in_array($executionCondition, $validTriggers, true)) {
				$viewer->assign('SELECTED_EXECUTION_CONDITION', $executionCondition);
				$workflowModel->set('execution_condition', $executionCondition);
			}
			$relationSourceValue = $request->get('relation_source_value');
			$relationDestValue = $request->get('relation_destination_value');
			if ($relationSourceValue !== null && $relationSourceValue !== '') {
				$viewer->assign('PREFILL_RELATION_SOURCE_VALUE', (string) $relationSourceValue);
			}
			if ($relationDestValue !== null && $relationDestValue !== '') {
				$viewer->assign('PREFILL_RELATION_DESTINATION_VALUE', (string) $relationDestValue);
			}
		}
		$db = \App\Database\PearDatabase::getInstance();
		$workflowManager = new \App\Modules\Workflow\VTWorkflowManager($db);
		$viewer->assign('MAX_ALLOWED_SCHEDULED_WORKFLOWS', $workflowManager->getMaxAllowedScheduledWorkflows());
		$viewer->assign('SCHEDULED_WORKFLOW_COUNT', $workflowManager->getScheduledWorkflowsCount());
		$viewer->assign('WORKFLOW_MODEL', $workflowModel);
		$viewer->assign('ALL_MODULES', \App\Modules\Settings\Workflows\Models\Module::getSupportedModules());
		$viewer->assign('TRIGGER_TYPES', \App\Modules\Settings\Workflows\Models\Module::getTriggerTypes());

		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('QUALIFIED_MODULE', $qualifiedModuleName);
		$viewer->assign('CURRENT_USER', $currentUser);
		$admin = \App\Modules\Users\Users::getActiveAdminUser();
		$viewer->assign('ACTIVE_ADMIN', $admin);
		$viewer->assign('WEEK_START_ID', $weekDays[$currentUser->get('dayoftheweek')]);
		
		// Prepare Step1-specific data for Step1 template
		$this->prepareWorkflowsStep1Data($viewer, $workflowModel);

		$viewer->assign('STEP_TEMPLATE', 'Step1.tpl');
		$viewer->view('EditView.tpl', $qualifiedModuleName);
	}
	
	/**
	 * Prepare data for Workflows Step1 template
	 * Moves function calls from template to controller for better MVC separation
	 */
	protected function prepareWorkflowsStep1Data($viewer, $workflowModel)
	{
		$workflowModelObj = $workflowModel->getWorkflowObject();
		// Prepare decoded schedule data
		$schdayofweek = $workflowModelObj->schdayofweek;
		$schdayofmonth = $workflowModelObj->schdayofmonth;
		$schannualdates = $workflowModelObj->schannualdates;
		
		$viewer->assign('DAY_OF_WEEK_DECODED', $schdayofweek ? \App\Utils\Json::decode($schdayofweek) : []);
		$viewer->assign('DAYS_OF_MONTH_DECODED', $schdayofmonth ? \App\Utils\Json::decode($schdayofmonth) : []);
		$viewer->assign('SPECIFIC_DATE_DECODED', $schannualdates ? \App\Utils\Json::decode($schannualdates) : []);
	}

	public function step2(\App\Http\Vtiger_Request $request)
	{

		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		$qualifiedModuleName = $request->getModule(false);

		$recordId = $request->get('record');

		if ($recordId) {
			$workFlowModel = \App\Modules\Settings\Workflows\Models\Record::getInstance($recordId);
			$selectedModule = $workFlowModel->getModule();
			$selectedModuleName = $selectedModule->getName();
		} else {
			$selectedModuleName = $request->get('module_name');
			$selectedModule = \App\Modules\Base\Models\Module::getInstance($selectedModuleName);
			$workFlowModel = \App\Modules\Settings\Workflows\Models\Record::getCleanInstance($selectedModuleName);
		}

		$requestData = $request->getAll();
		foreach ($requestData as $name => $value) {
			if ($name == 'schdayofweek' || $name == 'schdayofmonth' || $name == 'schannualdates') {
				if (is_string($value)) { // need to save these as json data
					$value = array($value);
				}
			}
			if ($name == 'summary')
				$value = htmlspecialchars($value);
			$workFlowModel->set($name, $value);
		}
		//Added to support advance filters
		$recordStructureInstance = \App\Modules\Settings\Workflows\Models\RecordStructure::getInstanceForWorkFlowModule($workFlowModel, \App\Modules\Settings\Workflows\Models\RecordStructure::RECORD_STRUCTURE_MODE_FILTER);
		$recordStructure = $recordStructureInstance->getStructure();
		$viewer->assign('RECORD_STRUCTURE', $recordStructure);
		$viewer->assign('WORKFLOW_MODEL', $workFlowModel);
		$viewer->assign('MODULE_MODEL', $selectedModule);
		$viewer->assign('SELECTED_MODULE_NAME', $selectedModuleName);
		$viewer->assign('DATE_FILTERS', \App\Modules\Base\Helpers\AdvancedFilter::getDateFilter($qualifiedModuleName));
		$viewer->assign('ADVANCED_FILTER_OPTIONS', \App\Modules\Settings\Workflows\Models\Field::getAdvancedFilterOptions());
		$viewer->assign('ADVANCED_FILTER_OPTIONS_BY_TYPE', \App\Modules\Settings\Workflows\Models\Field::getAdvancedFilterOpsByFieldType());
		$viewer->assign('COLUMNNAME_API', 'getWorkFlowFilterColumnName');
		$viewer->assign('FIELD_EXPRESSIONS', \App\Modules\Settings\Workflows\Models\Module::getExpressions());

		// Added to show filters only when saved from vtiger6
		if ($workFlowModel->isFilterSavedInNew()) {
			$viewer->assign('ADVANCE_CRITERIA', $workFlowModel->transformToAdvancedFilterCondition());
		} else {
			$viewer->assign('ADVANCE_CRITERIA', "");
		}

		$viewer->assign('IS_FILTER_SAVED_NEW', $workFlowModel->isFilterSavedInNew());
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('QUALIFIED_MODULE', $qualifiedModuleName);
		
		// Prepare Step2-specific data for Step2 template
		$this->prepareWorkflowsStep2Data($viewer, $workFlowModel);

		$viewer->view('Step2.tpl', $qualifiedModuleName);
	}
	
	/**
	 * Prepare data for Workflows Step2 template
	 * Moves function calls from template to controller for better MVC separation
	 */
	protected function prepareWorkflowsStep2Data($viewer, $workFlowModel)
	{
		$executionCondition = (int) $workFlowModel->get('execution_condition');
		$relationTrigger = null;
		if ($workFlowModel->getId()) {
			$relationTrigger = \App\Modules\Settings\Workflows\Models\RelationTrigger::getByWorkflowId((int) $workFlowModel->getId());
		}
		if (!$relationTrigger) {
			$relationTrigger = [
				'source_module' => \App\Modules\Settings\Workflows\Models\RelationTrigger::DEFAULT_SOURCE_MODULE,
				'destination_module' => \App\Modules\Settings\Workflows\Models\RelationTrigger::DEFAULT_DESTINATION_MODULE,
				'relation_table' => \App\Modules\Settings\Workflows\Models\RelationTrigger::DEFAULT_RELATION_TABLE,
				'relation_field' => \App\Modules\Settings\Workflows\Models\RelationTrigger::DEFAULT_RELATION_FIELD,
				'source_value' => '',
				'destination_value' => '',
				'once_per_pair' => 0,
			];
			if (!$workFlowModel->getId()) {
				$prefillSource = $workFlowModel->get('relation_source_value');
				$prefillDest = $workFlowModel->get('relation_destination_value');
				if ($prefillSource !== null && $prefillSource !== false && $prefillSource !== '') {
					$relationTrigger['source_value'] = (string) $prefillSource;
				}
				if ($prefillDest !== null && $prefillDest !== false && $prefillDest !== '') {
					$relationTrigger['destination_value'] = (string) $prefillDest;
				}
			}
		}
		$relationSourceModule = $relationTrigger['source_module'] ?? \App\Modules\Settings\Workflows\Models\RelationTrigger::DEFAULT_SOURCE_MODULE;
		$relationDestinationModule = $relationTrigger['destination_module'] ?? \App\Modules\Settings\Workflows\Models\RelationTrigger::DEFAULT_DESTINATION_MODULE;
		$viewer->assign('RELATION_TABLE', \App\Modules\Settings\Workflows\Models\RelationTrigger::resolveRelationTable(
			$relationSourceModule,
			$relationDestinationModule
		));
		$viewer->assign('RELATION_FIELD', \App\Modules\Settings\Workflows\Models\RelationTrigger::resolveRelationField(
			$relationSourceModule,
			$relationDestinationModule
		));
		$viewer->assign('IS_RELATION_MODIFY_TRIGGER', $executionCondition === \App\Modules\Workflow\VTWorkflowManager::$ON_RELATION_MODIFY);
		$viewer->assign('RELATION_TRIGGER', $relationTrigger);
		$viewer->assign('RELATION_STATUS_OPTIONS', \App\Modules\Settings\Workflows\Models\RelationTrigger::getRecruitmentStatusOptions());
		$viewer->assign('RELATION_SOURCE_MODULE', \App\Modules\Settings\Workflows\Models\RelationTrigger::DEFAULT_SOURCE_MODULE);
		$viewer->assign('RELATION_DESTINATION_MODULE', \App\Modules\Settings\Workflows\Models\RelationTrigger::DEFAULT_DESTINATION_MODULE);

		// Prepare JSON-encoded workflow data
		$conditions = $workFlowModel->get('conditions');
		$schdayofweek = $workFlowModel->get('schdayofweek');
		$schdayofmonth = $workFlowModel->get('schdayofmonth');
		$schannualdates = $workFlowModel->get('schannualdates');
		
		$viewer->assign('OLDER_CONDITIONS_JSON', \App\Utils\Json::encode($conditions ? $conditions : []));
		$viewer->assign('SCHDAYOFWEEK_JSON', \App\Utils\Json::encode($schdayofweek ? $schdayofweek : []));
		$viewer->assign('SCHDAYOFMONTH_JSON', \App\Utils\Json::encode($schdayofmonth ? $schdayofmonth : []));
		$viewer->assign('SCHANNUALDATES_JSON', \App\Utils\Json::encode($schannualdates ? $schannualdates : []));
		
		// Prepare field info JSON for AdvanceFilterCondition template
		$recordStructure = $viewer->getTemplateVars('RECORD_STRUCTURE');
		$fieldInfoJson = [];
		$fieldValidatorJson = [];
		if ($recordStructure) {
			foreach ($recordStructure as $blockLabel => $blockFields) {
				foreach ($blockFields as $fieldName => $fieldModel) {
					$fieldInfo = $fieldModel->getFieldInfo();
					$fieldInfoJson[$fieldName] = \App\Modules\Base\Helpers\Util::toSafeHTML(\App\Utils\Json::encode($fieldInfo));
					$validator = $fieldModel->getValidator();
					if ($validator) {
						$fieldValidatorJson[$fieldName] = \App\Utils\Json::encode($validator);
					}
				}
			}
		}
		$viewer->assign('FIELD_INFO_JSON', $fieldInfoJson);
		$viewer->assign('FIELD_VALIDATOR_JSON', $fieldValidatorJson);
	}

	public function Step3(\App\Http\Vtiger_Request $request)
	{
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		$qualifiedModuleName = $request->getModule(false);

		$recordId = $request->get('record');

		if ($recordId) {
			$workFlowModel = \App\Modules\Settings\Workflows\Models\Record::getInstance($recordId);
			$selectedModule = $workFlowModel->getModule();
			$selectedModuleName = $selectedModule->getName();
		} else {
			$selectedModuleName = $request->get('module_name');
			$selectedModule = \App\Modules\Base\Models\Module::getInstance($selectedModuleName);
			$workFlowModel = \App\Modules\Settings\Workflows\Models\Record::getCleanInstance($selectedModuleName);
		}

		$moduleModel = $workFlowModel->getModule();
		$taskTypes = \App\Modules\Settings\Workflows\Models\TaskType::getAllForModule($moduleModel);
		if ((int) $workFlowModel->get('execution_condition') === \App\Modules\Workflow\VTWorkflowManager::$ON_RELATION_MODIFY) {
			$taskTypes = array_values(array_filter($taskTypes, static function ($taskType) {
				return \App\Modules\Workflow\RelationWorkflowRunner::isAllowedTaskClass($taskType->getName());
			}));
		}
		$viewer->assign('TASK_TYPES', $taskTypes);
		$viewer->assign('SOURCE_MODULE', $selectedModuleName);
		$viewer->assign('RECORD', $recordId);
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('WORKFLOW_MODEL', $workFlowModel);
		$viewer->assign('TASK_LIST', $workFlowModel->getTasks());
		$viewer->assign('QUALIFIED_MODULE', $qualifiedModuleName);

		$viewer->view('Step3.tpl', $qualifiedModuleName);
	}

	public function getFooterScripts(\App\Http\Vtiger_Request $request)
	{
		$headerScriptInstances = parent::getFooterScripts($request);
		$moduleName = $request->getModule();

		$jsFileNames = array(
			'libraries.jquery.clipboardjs.clipboard',
			'modules.Settings.Vtiger.resources.Edit',
			"modules.Settings.$moduleName.resources.Edit",
			"modules.Settings.$moduleName.resources.Edit1",
			"modules.Settings.$moduleName.resources.Edit2",
			"modules.Settings.$moduleName.resources.Edit3",
			"modules.Settings.$moduleName.resources.AdvanceFilter",
			'~libraries/jquery/ckeditor/ckeditor.js',
			"modules.Base.resources.CkEditor",
			'~libraries/jquery/jquery.datepick.package-4.1.0/jquery.datepick.js',
		);

		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		$headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);
		return $headerScriptInstances;
	}

	public function getHeaderCss(\App\Http\Vtiger_Request $request)
	{
		$headerCssInstances = parent::getHeaderCss($request);
		$moduleName = $request->getModule();
		$cssFileNames = array(
			'~libraries/jquery/jquery.datepick.package-4.1.0/jquery.datepick.css',
		);
		$cssInstances = $this->checkAndConvertCssStyles($cssFileNames);
		$headerCssInstances = array_merge($cssInstances, $headerCssInstances);
		return $headerCssInstances;
	}
}
