<?php

namespace App\Modules\Settings\Workflows\Views;
use App\Modules\Settings\WorkflowsModels\TaskRecord;


/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * ********************************************************************************** */


class EditTask extends \App\Modules\Settings\Base\Views\Index
{

	public function process(\App\Http\Vtiger_Request $request)
	{
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		$qualifiedModuleName = $request->getModule(false);

		$recordId = $request->get('task_id');
		$workflowId = $request->get('for_workflow');

		$workflowModel = \App\Modules\Settings\Workflows\Models\Record::getInstance($workflowId);
		$taskTypes = $workflowModel->getTaskTypes();
		if ($recordId) {
			$taskModel = \App\Modules\Settings\Workflows\Models\TaskRecord::getInstance($recordId);
		} else {
			$taskType = $request->get('type');
			if (empty($taskType)) {
				$taskType = !empty($taskTypes[0]) ? $taskTypes[0]->getName() : 'VTEmailTask';
			}
			$taskModel = \App\Modules\Settings\Workflows\Models\TaskRecord::getCleanInstance($workflowModel, $taskType);
		}
		$taskTypeModel = $taskModel->getTaskType();
		$viewer->assign('TASK_TYPE_MODEL', $taskTypeModel);
		$viewer->assign('TASK_TEMPLATE_PATH', $taskTypeModel->getTemplatePath());
		$moduleModel = $workflowModel->getModule();
		$sourceModule = $moduleModel->getName();
		$dateTimeFields = $moduleModel->getFieldsByType(array('date', 'datetime'));

		$taskObject = $taskModel->getTaskObject();
		$taskType = get_class($taskObject);
		if ($taskType === 'VTCreateEntityTask') {
			$handlerClass = \App\Loader::getComponentClassName('Model', 'MappedFields', $sourceModule);
			$mfModel = new $handlerClass();
			$viewer->assign('TEMPLATES_MAPPING', $mfModel->getTemplatesByModule($sourceModule));
			if ($taskObject->entity_type && $taskObject->field_value_mapping) {
				$relationModuleModel = \App\Modules\Base\Models\Module::getInstance($taskObject->entity_type);
				$ownerFieldModels = $relationModuleModel->getFieldsByType('owner');

				$fieldMapping = \App\Utils\Json::decode($taskObject->field_value_mapping);
				foreach ($fieldMapping as $key => $mappingInfo) {
					if (array_key_exists($mappingInfo['fieldname'], $ownerFieldModels)) {
						if ($mappingInfo['value'] == 'assigned_user_id') {
							$fieldMapping[$key]['valuetype'] = 'fieldname';
						} else {
							$userRecordModel = \App\Modules\Users\Models\Record::getInstanceByName($mappingInfo['value']);
							if ($userRecordModel) {
								$ownerName = $userRecordModel->getId();
							} else {
								$groupRecordModel = \App\Modules\Settings\Groups\Models\Record::getInstance($mappingInfo['value']);
								$ownerName = $groupRecordModel->getId();
							}
							$fieldMapping[$key]['value'] = $ownerName;
						}
					}
				}
				$taskObject->field_value_mapping = \App\Utils\Json::encode($fieldMapping);
			}
		}
	if ($taskType === 'VTUpdateFieldsTask') {
		if ($sourceModule == "Documents") {
			$restrictFields = array('folderid', 'filename', 'filelocationtype');
			$viewer->assign('RESTRICTFIELDS', $restrictFields);
		}
	}
	if ($taskType === 'App\\Modules\\com_vtiger_workflow\\tasks\\VTSendPdf' || $taskType === 'VTSendPdf') {
		// Prepare PDF templates for the template
		$pdfTemplates = \App\Modules\Base\Models\PDF::getTemplatesByModule($sourceModule);
		$viewer->assign('PDF_TEMPLATES', $pdfTemplates);
		
		// Prepare SMTP accounts
		$smtpAccounts = \App\Mail::getAll();
		$viewer->assign('SMTP_ACCOUNTS', $smtpAccounts);
		
		// Prepare email templates
		$emailTemplates = \App\Mail::getTempleteList($sourceModule, 'PLL_RECORD');
		$viewer->assign('EMAIL_TEMPLATES', $emailTemplates);
	}
	$viewer->assign('SOURCE_MODULE', $sourceModule);
		$viewer->assign('MODULE_MODEL', $moduleModel);
		$viewer->assign('TASK_ID', $recordId);
		$viewer->assign('WORKFLOW_ID', $workflowId);
		$viewer->assign('DATETIME_FIELDS', $dateTimeFields);
		$viewer->assign('WORKFLOW_MODEL', $workflowModel);
		$viewer->assign('TASK_TYPES', $taskTypes);
		$viewer->assign('TASK_MODEL', $taskModel);
		$viewer->assign('CURRENTDATE', date('Y-n-j'));
		// Adding option Line Item block for Individual tax mode
		$individualTaxBlockLabel = \App\Runtime\Vtiger_Language_Handler::translate("LBL_LINEITEM_BLOCK_GROUP", $qualifiedModuleName);
		$individualTaxBlockValue = $viewer->view('LineItemsGroupTemplate.tpl', $qualifiedModuleName, $fetch = true);

		// Adding option Line Item block for group tax mode
		$groupTaxBlockLabel = \App\Runtime\Vtiger_Language_Handler::translate("LBL_LINEITEM_BLOCK_INDIVIDUAL", $qualifiedModuleName);
		$groupTaxBlockValue = $viewer->view('LineItemsIndividualTemplate.tpl', $qualifiedModuleName, $fetch = true);

		$templateVariables = array(
			$individualTaxBlockValue => $individualTaxBlockLabel,
			$groupTaxBlockValue => $groupTaxBlockLabel
		);
		$viewer->assign('TEMPLATE_VARIABLES', $templateVariables);
		$viewer->assign('TASK_OBJECT', $taskObject);
		$viewer->assign('FIELD_EXPRESSIONS', \App\Modules\Settings\Workflows\Models\Module::getExpressions());
		$userModel = $request->getUser();
		$viewer->assign('dateFormat', $userModel->get('date_format'));
		$viewer->assign('timeFormat', $userModel->get('hour_format'));
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('QUALIFIED_MODULE', $qualifiedModuleName);
		$emailFieldoptions = [];
		$textParser = \App\TextParser::getInstance($sourceModule);
		foreach ($textParser->getRecordVariable('email') as $blockName => $fields) {
			$blockName = \App\Runtime\Vtiger_Language_Handler::translate($blockName, $sourceModule);
			foreach ($fields as $field) {
				$emailFieldoptions[$blockName][$field['var_value']] = \App\Runtime\Vtiger_Language_Handler::translate($field['label'], $sourceModule);
			}
		}
		foreach ($textParser->getRelatedVariable('email') as $modules) {
			foreach ($modules as $blockName => $fields) {
				$blockName = \App\Runtime\Vtiger_Language_Handler::translate($blockName, $sourceModule);
				foreach ($fields as $field) {
					$emailFieldoptions[$blockName][$field['var_value']] = \App\Runtime\Vtiger_Language_Handler::translate($field['label'], $sourceModule);
				}
			}
		}
		$fromEmailFieldOptions = array_merge(['' => ['' => \App\Runtime\Vtiger_Language_Handler::translate('Optional', $qualifiedModuleName)]], $emailFieldoptions);
		$assignedToValues = [
			\App\Runtime\Vtiger_Language_Handler::translate('LBL_USERS') => \App\Fields\Owner::getInstance()->getAccessibleUsers(),
			\App\Runtime\Vtiger_Language_Handler::translate('LBL_GROUPS') => \App\Fields\Owner::getInstance()->getAccessibleGroups()
		];
		$viewer->assign('TEXT_PARSER', $textParser);
		$viewer->assign('ASSIGNED_TO', $assignedToValues);
		$viewer->assign('EMAIL_FIELD_OPTION', $emailFieldoptions);
		$viewer->assign('FROM_EMAIL_FIELD_OPTION', $fromEmailFieldOptions);
		
		// Prepare workflow task-specific data for task templates
		$this->prepareWorkflowTaskData($viewer, $taskObject, $taskType, $sourceModule);
		
		$viewer->view('EditTask.tpl', $qualifiedModuleName);
	}
	
	/**
	 * Prepare data for workflow task templates
	 * Moves function calls from templates to controller for better MVC separation
	 */
	protected function prepareWorkflowTaskData($viewer, $taskObject, $taskType, $sourceModule)
	{
		// Prepare datetime value for 'now'
		$dateTimeValue = \App\Modules\Base\UiTypes\Datetime::getDateTimeValue('now');
		$viewer->assign('DATE_TIME_VALUE_NOW', $dateTimeValue);
		
		// Prepare notification type picklist values
		$viewer->assign('NOTIFICATION_TYPE_VALUES', \App\Fields\Picklist::getPickListValues('notification_type'));
		
		// Prepare privilege members
		$viewer->assign('PRIVILEGE_MEMBERS', \App\PrivilegeUtil::getMembers());
		
		// Prepare mail accounts for email tasks
		$viewer->assign('MAIL_ACCOUNTS', \App\Mail::getAll());
		
		// Prepare email templates for email/notification tasks
		$viewer->assign('EMAIL_TEMPLATES', \App\Mail::getTempleteList($sourceModule, 'PLL_RECORD'));
		
		// Prepare activity status picklist values for VTCreateTodoTask
		$viewer->assign('ACTIVITY_STATUS_PICKLIST_VALUES', \App\Fields\Picklist::getPickListValues('activitystatus'));
		
		// Prepare taskFields JSON for VTCreateEntityTask
		if ($taskType === 'VTCreateEntityTask' && method_exists($taskObject, 'getFieldNames')) {
			$taskFields = $taskObject->getFieldNames();
			$viewer->assign('TASK_FIELDS_JSON', \App\Modules\Base\Helpers\Util::toSafeHTML(\App\Utils\Json::encode($taskFields)));
		}
		
		// Prepare field value mappings with JSON encoding/decoding
		if (isset($taskObject->field_value_mapping) && !empty($taskObject->field_value_mapping)) {
			$fieldValueMapping = \App\Utils\Json::decode($taskObject->field_value_mapping);
			$viewer->assign('FIELD_VALUE_MAPPING_DECODED', $fieldValueMapping);
			$viewer->assign('FIELD_VALUE_MAPPING_JSON', \App\Modules\Base\Helpers\Util::toSafeHTML($taskObject->field_value_mapping));
		} else {
			$viewer->assign('FIELD_VALUE_MAPPING_DECODED', []);
			$viewer->assign('FIELD_VALUE_MAPPING_JSON', '');
		}
		
		// Prepare field info JSON for each field (used in VTUpdateFieldsTask)
		$moduleModel = $viewer->getTemplateVars('MODULE_MODEL');
		if ($moduleModel) {
			$fieldInfoJson = [];
			foreach ($moduleModel->getFields() as $fieldModel) {
				$fieldName = $fieldModel->get('name');
				$fieldInfo = $fieldModel->getFieldInfo();
				$fieldInfoJson[$fieldName] = \App\Modules\Base\Helpers\Util::toSafeHTML(\App\Utils\Json::encode($fieldInfo));
			}
			$viewer->assign('FIELD_INFO_JSON', $fieldInfoJson);
			
			// Prepare field info JSON for related fields (used in VTUpdateRelatedFieldTask)
			$relatedFieldInfoJson = [];
			foreach ($moduleModel->getRelations() as $relationModel) {
				$relationModuleName = $relationModel->getRelationModuleName();
				$relationModuleModel = $relationModel->getRelationModuleModel();
				foreach ($relationModuleModel->getFields() as $fieldModel) {
					$fieldName = $relationModuleName . '::' . $fieldModel->get('name');
					$fieldInfo = $fieldModel->getFieldInfo();
					$relatedFieldInfoJson[$fieldName] = \App\Modules\Base\Helpers\Util::toSafeHTML(\App\Utils\Json::encode($fieldInfo));
				}
			}
			$viewer->assign('RELATED_FIELD_INFO_JSON', $relatedFieldInfoJson);
		}
	}
}
