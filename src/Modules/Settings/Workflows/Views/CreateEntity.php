<?php

namespace App\Modules\Settings\Workflows\Views;


/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * ********************************************************************************** */

class CreateEntity extends \App\Modules\Settings\Base\Views\Index
{

	public function process(\App\Http\Vtiger_Request $request)
	{
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		$qualifiedModuleName = $request->getModule(false);

		$workflowId = $request->get('for_workflow');
		$workflowModel = \App\Modules\Settings\Workflows\Models\Record::getInstance($workflowId);

		$relatedModule = $request->get('relatedModule');
		$relatedModuleModel = \App\Modules\Base\Models\Module::getInstance($relatedModule);

		$workflowModuleModel = $workflowModel->getModule();

		$viewer->assign('MAPPING_PANEL', $request->get('mappingPanel'));
		$viewer->assign('WORKFLOW_MODEL', $workflowModel);
		$viewer->assign('REFERENCE_FIELD_NAME', $workflowModel->getReferenceFieldName($relatedModule));
		$viewer->assign('RELATED_MODULE_MODEL', $relatedModuleModel);
		$viewer->assign('FIELD_EXPRESSIONS', \App\Modules\Settings\Workflows\Models\Module::getExpressions());
		$viewer->assign('MODULE_MODEL', $workflowModuleModel);
		$viewer->assign('SOURCE_MODULE', $workflowModuleModel->getName());
		$viewer->assign('RELATED_MODULE_MODEL_NAME', '');
		$viewer->assign('QUALIFIED_MODULE', $qualifiedModuleName);
		
		// Prepare CreateEntity-specific data for CreateEntity template
		$this->prepareCreateEntityData($viewer, $workflowModel, $relatedModuleModel);
		
		$viewer->view('CreateEntity.tpl', $qualifiedModuleName);
	}
	
	/**
	 * Prepare data for CreateEntity template
	 * Moves function calls from template to controller for better MVC separation
	 */
	protected function prepareCreateEntityData($viewer, $workflowModel, $relatedModuleModel)
	{
		$taskObject = null;
		$taskId = $viewer->getTemplateVars('TASK_ID');
		if ($taskId) {
			$taskModel = \App\Modules\Settings\Workflows\Models\TaskRecord::getInstance($taskId);
			$taskObject = $taskModel->getTaskObject();
		}
		
		// Prepare field value mapping with JSON decoding
		if ($taskObject && isset($taskObject->field_value_mapping) && !empty($taskObject->field_value_mapping)) {
			$fieldValueMapping = \App\Json::decode($taskObject->field_value_mapping);
			$viewer->assign('FIELD_VALUE_MAPPING_DECODED', $fieldValueMapping);
		} else {
			$viewer->assign('FIELD_VALUE_MAPPING_DECODED', []);
		}
		
		// Prepare field info JSON for related module fields
		if ($relatedModuleModel) {
			$fieldInfoJson = [];
			foreach ($relatedModuleModel->getFields() as $fieldModel) {
				$fieldName = $fieldModel->get('name');
				$fieldInfo = $fieldModel->getFieldInfo();
				// Handle owner field special options
				if ($fieldModel->getFieldDataType() == 'owner') {
					$specialOption = [\App\Runtime\Vtiger_Language_Handler::translate('LBL_SPECIAL_OPTIONS', $viewer->getTemplateVars('QUALIFIED_MODULE')) => ['assigned_user_id' => \App\Runtime\Vtiger_Language_Handler::translate('LBL_PARENT_OWNER', $viewer->getTemplateVars('QUALIFIED_MODULE'))]];
					$fieldInfo['picklistvalues'] = array_merge($fieldInfo['picklistvalues'], $specialOption);
				}
				$fieldInfoJson[$fieldName] = \App\Modules\Base\Helpers\Util::toSafeHTML(\App\Json::encode($fieldInfo));
			}
			$viewer->assign('RELATED_FIELD_INFO_JSON', $fieldInfoJson);
		}
	}
}
