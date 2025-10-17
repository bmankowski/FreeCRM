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


class EditTask extends \App\Modules\Settings\Vtiger\Views\Index
{

	public function process(\App\Http\Vtiger_Request $request)
	{
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		$qualifiedModuleName = $request->getModule(false);

		$recordId = $request->get('task_id');
		$workflowId = $request->get('for_workflow');

		$workflowModel = Settings_Workflows_Record_Model::getInstance($workflowId);
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
			$handlerClass = \App\Vtiger_Loader::getComponentClassName('Model', 'MappedFields', $sourceModule);
			$mfModel = new $handlerClass();
			$viewer->assign('TEMPLATES_MAPPING', $mfModel->getTemplatesByModule($sourceModule));
			if ($taskObject->entity_type && $taskObject->field_value_mapping) {
				$relationModuleModel = \App\Modules\Vtiger\Models\Module::getInstance($taskObject->entity_type);
				$ownerFieldModels = $relationModuleModel->getFieldsByType('owner');

				$fieldMapping = \App\Json::decode($taskObject->field_value_mapping);
				foreach ($fieldMapping as $key => $mappingInfo) {
					if (array_key_exists($mappingInfo['fieldname'], $ownerFieldModels)) {
						if ($mappingInfo['value'] == 'assigned_user_id') {
							$fieldMapping[$key]['valuetype'] = 'fieldname';
						} else {
							$userRecordModel = \App\Modules\Users\Models\Record::getInstanceByName($mappingInfo['value']);
							if ($userRecordModel) {
								$ownerName = $userRecordModel->getId();
							} else {
								$groupRecordModel = Settings_Groups_Record_Model::getInstance($mappingInfo['value']);
								$ownerName = $groupRecordModel->getId();
							}
							$fieldMapping[$key]['value'] = $ownerName;
						}
					}
				}
				$taskObject->field_value_mapping = \App\Json::encode($fieldMapping);
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
		$pdfTemplates = \App\Modules\Vtiger\Models\PDF::getTemplatesByModule($sourceModule);
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
		$userModel = \App\Modules\Users\Models\Record::getCurrentUserModel();
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
			\LanguageTranslator::translate('LBL_USERS') => \App\Fields\Owner::getInstance()->getAccessibleUsers(),
			\LanguageTranslator::translate('LBL_GROUPS') => \App\Fields\Owner::getInstance()->getAccessibleGroups()
		];
		$viewer->assign('TEXT_PARSER', $textParser);
		$viewer->assign('ASSIGNED_TO', $assignedToValues);
		$viewer->assign('EMAIL_FIELD_OPTION', $emailFieldoptions);
		$viewer->assign('FROM_EMAIL_FIELD_OPTION', $fromEmailFieldOptions);
		$viewer->view('EditTask.tpl', $qualifiedModuleName);
	}
}
