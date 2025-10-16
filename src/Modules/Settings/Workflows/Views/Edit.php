<?php

namespace FreeCRM\Modules\Settings\Workflows\Views;
use FreeCRM\Modules\Settings\WorkflowsModels\TaskType;
use FreeCRM\Modules\Settings\WorkflowsModels\RecordStructure;


/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * ********************************************************************************** */

use FreeCRM\Modules\com_vtiger_workflow\VTWorkflowManager as VTWorkflowManager;

use FreeCRM\Modules\Settings\Workflows\Models\Record as Settings_Workflows_Record_Model;
class Edit extends \FreeCRM\Modules\Settings\Vtiger\Views\Index
{

	public function process(\FreeCRM\Http\Vtiger_Request $request)
	{
		$mode = $request->getMode();
		if ($mode) {
			$this->$mode($request);
		} else {
			$this->step1($request);
		}
	}

	public function preProcess(\FreeCRM\Http\Vtiger_Request $request, $display = true)
	{
		parent::preProcess($request);
		$viewer = $this->getViewer($request);

		$recordId = $request->get('record');
		$viewer->assign('RECORDID', $recordId);
		if ($recordId) {
			$workflowModel = Settings_Workflows_Record_Model::getInstance($recordId);
			$viewer->assign('WORKFLOW_MODEL', $workflowModel);
		}
		$viewer->assign('RECORD_MODE', $request->getMode());
		$viewer->view('EditHeader.tpl', $request->getModule(false));
	}

	public function step1(\FreeCRM\Http\Vtiger_Request $request)
	{
		$currentUser = \FreeCRM\Modules\Users\Models\Record::getCurrentUserModel();
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		$qualifiedModuleName = $request->getModule(false);
		$weekDays = ['Sunday' => 0, 'Monday' => 1, 'Tuesday' => 2, 'Wednesday' => 3, 'Thursday' => 4, 'Friday' => 5, 'Saturday' => 6];

		$recordId = $request->get('record');
		if ($recordId) {
			$workflowModel = Settings_Workflows_Record_Model::getInstance($recordId);
			$viewer->assign('RECORDID', $recordId);
			$viewer->assign('MODULE_MODEL', $workflowModel->getModule());
			$viewer->assign('MODE', 'edit');
		} else {
			$workflowModel = Settings_Workflows_Record_Model::getCleanInstance($moduleName);
			$selectedModule = $request->get('source_module');
			if (!empty($selectedModule)) {
				$viewer->assign('SELECTED_MODULE', $selectedModule);
			}
		}
		$db = \FreeCRM\database\PearDatabase::getInstance();
		$workflowManager = new VTWorkflowManager($db);
		$viewer->assign('MAX_ALLOWED_SCHEDULED_WORKFLOWS', $workflowManager->getMaxAllowedScheduledWorkflows());
		$viewer->assign('SCHEDULED_WORKFLOW_COUNT', $workflowManager->getScheduledWorkflowsCount());
		$viewer->assign('WORKFLOW_MODEL', $workflowModel);
		$viewer->assign('ALL_MODULES', \FreeCRM\Modules\Settings\Workflows\Models\Module::getSupportedModules());
		$viewer->assign('TRIGGER_TYPES', \FreeCRM\Modules\Settings\Workflows\Models\Module::getTriggerTypes());

		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('QUALIFIED_MODULE', $qualifiedModuleName);
		$viewer->assign('CURRENT_USER', $currentUser);
		$admin = Users::getActiveAdminUser();
		$viewer->assign('ACTIVE_ADMIN', $admin);
		$viewer->assign('WEEK_START_ID', $weekDays[$currentUser->get('dayoftheweek')]);
		$viewer->view('Step1.tpl', $qualifiedModuleName);
	}

	public function step2(\FreeCRM\Http\Vtiger_Request $request)
	{

		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		$qualifiedModuleName = $request->getModule(false);

		$recordId = $request->get('record');

		if ($recordId) {
			$workFlowModel = Settings_Workflows_Record_Model::getInstance($recordId);
			$selectedModule = $workFlowModel->getModule();
			$selectedModuleName = $selectedModule->getName();
		} else {
			$selectedModuleName = $request->get('module_name');
			$selectedModule = \FreeCRM\Modules\Vtiger\Models\Module::getInstance($selectedModuleName);
			$workFlowModel = Settings_Workflows_Record_Model::getCleanInstance($selectedModuleName);
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
		$recordStructureInstance = \FreeCRM\Modules\Settings\Workflows\Models\RecordStructure::getInstanceForWorkFlowModule($workFlowModel, \FreeCRM\Modules\Settings\Workflows\Models\RecordStructure::RECORD_STRUCTURE_MODE_FILTER);
		$recordStructure = $recordStructureInstance->getStructure();
		$viewer->assign('RECORD_STRUCTURE', $recordStructure);
		$viewer->assign('WORKFLOW_MODEL', $workFlowModel);
		$viewer->assign('MODULE_MODEL', $selectedModule);
		$viewer->assign('SELECTED_MODULE_NAME', $selectedModuleName);
		$viewer->assign('DATE_FILTERS', \Vtiger_AdvancedFilter_Helper::getDateFilter($qualifiedModuleName));
		$viewer->assign('ADVANCED_FILTER_OPTIONS', \FreeCRM\Modules\Settings\Workflows\Models\Field::getAdvancedFilterOptions());
		$viewer->assign('ADVANCED_FILTER_OPTIONS_BY_TYPE', \FreeCRM\Modules\Settings\Workflows\Models\Field::getAdvancedFilterOpsByFieldType());
		$viewer->assign('COLUMNNAME_API', 'getWorkFlowFilterColumnName');
		$viewer->assign('FIELD_EXPRESSIONS', \FreeCRM\Modules\Settings\Workflows\Models\Module::getExpressions());

		// Added to show filters only when saved from vtiger6
		if ($workFlowModel->isFilterSavedInNew()) {
			$viewer->assign('ADVANCE_CRITERIA', $workFlowModel->transformToAdvancedFilterCondition());
		} else {
			$viewer->assign('ADVANCE_CRITERIA', "");
		}

		$viewer->assign('IS_FILTER_SAVED_NEW', $workFlowModel->isFilterSavedInNew());
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('QUALIFIED_MODULE', $qualifiedModuleName);

		$viewer->view('Step2.tpl', $qualifiedModuleName);
	}

	public function Step3(\FreeCRM\Http\Vtiger_Request $request)
	{
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		$qualifiedModuleName = $request->getModule(false);

		$recordId = $request->get('record');

		if ($recordId) {
			$workFlowModel = Settings_Workflows_Record_Model::getInstance($recordId);
			$selectedModule = $workFlowModel->getModule();
			$selectedModuleName = $selectedModule->getName();
		} else {
			$selectedModuleName = $request->get('module_name');
			$selectedModule = \FreeCRM\Modules\Vtiger\Models\Module::getInstance($selectedModuleName);
			$workFlowModel = Settings_Workflows_Record_Model::getCleanInstance($selectedModuleName);
		}

		$moduleModel = $workFlowModel->getModule();
		$viewer->assign('TASK_TYPES', \FreeCRM\Modules\Settings\Workflows\Models\TaskType::getAllForModule($moduleModel));
		$viewer->assign('SOURCE_MODULE', $selectedModuleName);
		$viewer->assign('RECORD', $recordId);
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('WORKFLOW_MODEL', $workFlowModel);
		$viewer->assign('TASK_LIST', $workFlowModel->getTasks());
		$viewer->assign('QUALIFIED_MODULE', $qualifiedModuleName);

		$viewer->view('Step3.tpl', $qualifiedModuleName);
	}

	public function getFooterScripts(\FreeCRM\Http\Vtiger_Request $request)
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
			"modules.Vtiger.resources.CkEditor",
			'~libraries/jquery/jquery.datepick.package-4.1.0/jquery.datepick.js',
		);

		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		$headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);
		return $headerScriptInstances;
	}

	public function getHeaderCss(\FreeCRM\Http\Vtiger_Request $request)
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
