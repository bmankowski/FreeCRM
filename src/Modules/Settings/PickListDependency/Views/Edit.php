<?php

namespace App\Modules\Settings\PickListDependency\Views;


/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */


class Edit extends \App\Modules\Settings\Base\Views\Index
{

	public function process(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$qualifiedModuleName = $request->getModule(false);

		$moduleModelList = \App\Modules\Settings\PickListDependency\Models\Module::getPicklistSupportedModules();

		$selectedModule = $request->get('sourceModule');
		if (empty($selectedModule)) {
			$selectedModule = $moduleModelList[0]->name;
		}
		$sourceField = $request->get('sourcefield');
		$targetField = $request->get('targetfield');
		$recordModel = \App\Modules\Settings\PickListDependency\Models\Record::getInstance($selectedModule, $sourceField, $targetField);

		$dependencyGraph = false;
		if (!empty($sourceField) && !empty($targetField)) {
			$dependencyGraph = $this->getDependencyGraph($request);
		}

		$viewer = $this->getViewer($request);
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('RECORD_MODEL', $recordModel);
		$viewer->assign('SELECTED_MODULE', $selectedModule);
		$viewer->assign('PICKLIST_FIELDS', $recordModel->getAllPickListFields());
		$viewer->assign('PICKLIST_MODULES_LIST', $moduleModelList);
		$viewer->assign('DEPENDENCY_GRAPH', $dependencyGraph);
		$viewer->assign('QUALIFIED_MODULE', $qualifiedModuleName);

		$viewer->view('EditView.tpl', $qualifiedModuleName);
	}

	public function getDependencyGraph(\App\Http\Vtiger_Request $request)
	{
		$qualifiedName = $request->getModule(false);
		$module = $request->get('sourceModule');
		$sourceField = $request->get('sourcefield');
		$targetField = $request->get('targetfield');
		$recordModel = \App\Modules\Settings\PickListDependency\Models\Record::getInstance($module, $sourceField, $targetField);
		$valueMapping = $recordModel->getPickListDependency();
		$nonMappedSourceValues = $recordModel->getNonMappedSourcePickListValues();

		$viewer = $this->getViewer($request);
		$viewer->assign('MAPPED_VALUES', $valueMapping);
		$viewer->assign('SOURCE_PICKLIST_VALUES', $recordModel->getSourcePickListValues());
		$viewer->assign('TARGET_PICKLIST_VALUES', $recordModel->getTargetPickListValues());
		$viewer->assign('NON_MAPPED_SOURCE_VALUES', $nonMappedSourceValues);
		$viewer->assign('QUALIFIED_MODULE', $qualifiedName);
		$viewer->assign('RECORD_MODEL', $recordModel);
		
		// Prepare PickListDependency DependencyGraph-specific data for DependencyGraph template
		$this->preparePickListDependencyGraphData($viewer, $recordModel->getSourcePickListValues(), $recordModel->getTargetPickListValues());

		return $viewer->view('DependencyGraph.tpl', $qualifiedName, true);
	}
	
	/**
	 * Prepare data for PickListDependency DependencyGraph template
	 * Moves function calls from template to controller for better MVC separation
	 */
	protected function preparePickListDependencyGraphData($viewer, $sourcePicklistValues, $targetPicklistValues)
	{
		// Prepare JSON-encoded source picklist values with toSafeHTML
		$sourceValuesJson = \App\Utils\Json::encode($sourcePicklistValues);
		$viewer->assign('SOURCE_PICKLIST_VALUES_JSON', \App\Modules\Base\Helpers\Util::toSafeHTML($sourceValuesJson));
		
		// Prepare safe HTML for source picklist values
		$safeSourceValues = [];
		foreach ($sourcePicklistValues as $value) {
			$safeSourceValues[$value] = \App\Modules\Base\Helpers\Util::toSafeHTML($value);
		}
		$viewer->assign('SAFE_SOURCE_VALUES', $safeSourceValues);
		
		// Prepare safe HTML for target picklist values
		$safeTargetValues = [];
		foreach ($targetPicklistValues as $value) {
			$safeTargetValues[$value] = \App\Modules\Base\Helpers\Util::toSafeHTML($value);
		}
		$viewer->assign('SAFE_TARGET_VALUES', $safeTargetValues);
	}

	/**
	 * Function to get the list of Script models to be included
	 * @param \App\Http\Vtiger_Request $request
	 * @return <Array> - List of ScriptAsset instances
	 */
	public function getFooterScripts(\App\Http\Vtiger_Request $request)
	{
		$headerScriptInstances = parent::getFooterScripts($request);
		$moduleName = $request->getModule();

		$jsFileNames = array(
			'~libraries/jquery/malihu-custom-scrollbar/js/jquery.mCustomScrollbar.concat.min.js',
		);

		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		$headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);
		return $headerScriptInstances;
	}

	public function getHeaderCss(\App\Http\Vtiger_Request $request)
	{
		$headerCssInstances = parent::getHeaderCss($request);

		$cssFileNames = array(
			'~libraries/jquery/malihu-custom-scrollbar/css/jquery.mCustomScrollbar.css',
		);
		$cssInstances = $this->checkAndConvertCssStyles($cssFileNames);
		$headerCssInstances = array_merge($headerCssInstances, $cssInstances);

		return $headerCssInstances;
	}
}
