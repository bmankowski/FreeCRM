<?php

namespace App\Modules\Settings\LayoutEditor\Views;


/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * ********************************************************************************** */

class Index extends \App\Modules\Settings\Base\Views\Index
{

	public function __construct()
	{
		$this->exposeMethod('showFieldLayout');
		$this->exposeMethod('showRelatedListLayout');
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$mode = $request->getMode();
		if ($this->isMethodExposed($mode)) {
			$this->invokeExposedMethod($mode, $request);
		} else {
			//by default show field layout
			$this->showFieldLayout($request);
		}
	}

	public function showFieldLayout(\App\Http\Vtiger_Request $request)
	{
		$sourceModule = $request->get('sourceModule');
		$supportedModulesList = \App\Modules\Settings\LayoutEditor\Models\Module::getSupportedModules();

		if (empty($sourceModule)) {
			//To get the first element
			$sourceModule = reset($supportedModulesList);
		}
		$moduleModel = \App\Modules\Settings\LayoutEditor\Models\Module::getInstanceByName($sourceModule);
		$fieldModels = $moduleModel->getFields();
		$blockModels = $moduleModel->getBlocks();

		$blockIdFieldMap = [];
		$inactiveFields = [];
		foreach ($fieldModels as $fieldModel) {
			$blockIdFieldMap[$fieldModel->getBlockId()][$fieldModel->getName()] = $fieldModel;
			if (!$fieldModel->isActiveField()) {
				$inactiveFields[$fieldModel->getBlockId()][$fieldModel->getId()] = \App\Runtime\Vtiger_Language_Handler::translate($fieldModel->get('label'), $sourceModule);
			}
		}

		foreach ($blockModels as $blockLabel => $blockModel) {
			if (isset($blockIdFieldMap[$blockModel->get('id')])) {
				$fieldModelList = $blockIdFieldMap[$blockModel->get('id')];
				$blockModel->setFields($fieldModelList);
			}
		}

		$qualifiedModule = $request->getModule(false);
		$moduleName = $request->getModule();

		$viewer = $this->getViewer($request);
		$viewer->assign('SELECTED_MODULE_NAME', $sourceModule);
		$viewer->assign('SUPPORTED_MODULES', $supportedModulesList);
		$viewer->assign('SELECTED_MODULE_MODEL', $moduleModel);
		$viewer->assign('BLOCKS', $blockModels);
		$viewer->assign('ADD_SUPPORTED_FIELD_TYPES', $moduleModel->getAddSupportedFieldTypes());
		$viewer->assign('DISPLAY_TYPE_LIST', \App\Modules\Base\Models\Field::showDisplayTypeList());
		$viewer->assign('USER_MODEL', $request->getUser());
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('QUALIFIED_MODULE', $qualifiedModule);
		$viewer->assign('PARENT_MODULE', $request->get('parent'));
		$viewer->assign('VIEW', $request->get('view'));
		$viewer->assign('IN_ACTIVE_FIELDS', $inactiveFields);
		$viewer->assign('IS_INVENTORY', $moduleModel->isInventory());
		$viewer->assign('INVENTORY_MODEL', \App\Modules\Base\Models\InventoryField::getInstance($sourceModule));
		
		// Prepare LayoutEditor FieldLayout-specific data for FieldLayout template (includes CreateFieldModal)
		$this->prepareLayoutEditorFieldLayoutData($viewer, $inactiveFields);
		
		// Check if this is an AJAX request - if so, return only content without MainLayout
		if ($request->isAjax()) {
			$viewer->view('FieldLayout.tpl', $qualifiedModule);
		} else {
			$viewer->view('Index.tpl', $qualifiedModule);
		}
	}
	
	/**
	 * Prepare data for LayoutEditor FieldLayout template (includes CreateFieldModal)
	 * Moves function calls from template to controller for better MVC separation
	 */
	protected function prepareLayoutEditorFieldLayoutData($viewer, $inactiveFields)
	{
		// Prepare JSON-encoded inactive fields
		$viewer->assign('IN_ACTIVE_FIELDS_JSON', \App\Utils\Json::encode($inactiveFields));
		
		// Prepare validator JSON for CreateFieldModal
		$viewer->assign('FIELD_LABEL_VALIDATOR_JSON', \App\Utils\Json::encode([['name'=>'FieldLabel']]));
		$viewer->assign('FIELD_NAME_VALIDATOR_JSON', \App\Utils\Json::encode([['name'=>'fieldName']]));
		$viewer->assign('PICKLIST_FIELD_VALUES_VALIDATOR_JSON', \App\Utils\Json::encode([['name'=>'PicklistFieldValues']]));
	}

	public function showRelatedListLayout(\App\Http\Vtiger_Request $request)
	{
		$sourceModule = $request->get('sourceModule');
		$supportedModulesList = \App\Modules\Settings\LayoutEditor\Models\Module::getSupportedModules();

		if (empty($sourceModule)) {
			//To get the first element
			$moduleName = reset($supportedModulesList);
			$sourceModule = \App\Modules\Base\Models\Module::getInstance($moduleName)->getName();
		}
		$moduleModel = \App\Modules\Settings\LayoutEditor\Models\Module::getInstanceByName($sourceModule);
		$relatedModuleModels = $moduleModel->getRelations();

		$qualifiedModule = $request->getModule(false);
		$moduleName = $request->getModule();
		
		$viewer = $this->getViewer($request);
		$viewer->assign('SELECTED_MODULE_NAME', $sourceModule);
		$viewer->assign('SUPPORTED_MODULES', $supportedModulesList);
		$viewer->assign('RELATED_MODULES', $relatedModuleModels);
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('MODULE_MODEL', $moduleModel);
		$viewer->assign('QUALIFIED_MODULE', $qualifiedModule);
		$viewer->assign('PARENT_MODULE', $request->get('parent'));
		$viewer->assign('VIEW', $request->get('view'));
		
		// Prepare LayoutEditor RelatedList-specific data for RelatedList template
		$this->prepareLayoutEditorRelatedListData($viewer, $relatedModuleModels);
		
		// Check if this is an AJAX request - if so, return only content without MainLayout
		if ($request->isAjax()) {
			$viewer->view('RelatedList.tpl', $qualifiedModule);
		} else {
			$viewer->view('RelatedListIndex.tpl', $qualifiedModule);
		}
	}
	
	/**
	 * Prepare data for LayoutEditor RelatedList template
	 * Moves function calls from template to controller for better MVC separation
	 */
	protected function prepareLayoutEditorRelatedListData($viewer, $relatedModuleModels)
	{
		// Prepare relation types and actions
		$viewer->assign('RELATIONS_TYPES', \App\Modules\Settings\LayoutEditor\Models\Module::getRelationsTypes());
		$viewer->assign('RELATIONS_ACTIONS', \App\Modules\Settings\LayoutEditor\Models\Module::getRelationsActions());
		
		// Prepare record structures and inventory fields for each related module
		$recordStructures = [];
		$inventoryFields = [];
		$selectedFields = [];
		foreach ($relatedModuleModels as $moduleModel) {
			$relatedModuleName = $moduleModel->getRelationModuleName();
			$relatedModuleModel = $moduleModel->getRelationModuleModel();
			
			// Prepare record structure
			$recordStructures[$moduleModel->getId()] = \App\Modules\Base\Models\RecordStructure::getInstanceForModule($relatedModuleModel);
			
			// Prepare inventory fields if module has inventory
			if ($relatedModuleModel->isInventory()) {
				$inventoryFields[$moduleModel->getId()] = \App\Modules\Base\Models\InventoryField::getInstance($relatedModuleName);
			}
			
			// Prepare selected fields
			$selectedFields[$moduleModel->getId()] = \App\Modules\Settings\LayoutEditor\Models\Module::getRelationFields($moduleModel->getId());
		}
		$viewer->assign('RECORD_STRUCTURES', $recordStructures);
		$viewer->assign('INVENTORY_FIELDS', $inventoryFields);
		$viewer->assign('SELECTED_FIELDS', $selectedFields);
		
		// Prepare developer config flags
		$viewer->assign('CHANGE_RELATIONS_ENABLED', \App\Core\AppConfig::developer('CHANGE_RELATIONS'));
	}

	public function getFooterScripts(\App\Http\Vtiger_Request $request)
	{
		$headerScriptInstances = parent::getFooterScripts($request);
		$moduleName = $request->getModule();
		$jsFileNames = [
			'libraries.jquery.clipboardjs.clipboard',
			'modules.Settings.Vtiger.resources.Index',
			'modules.Settings.LayoutEditor.resources.LayoutEditor'
		];
		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		$headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);
		return $headerScriptInstances;
	}
}
