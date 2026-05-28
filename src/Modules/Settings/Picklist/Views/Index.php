<?php

namespace App\Modules\Settings\Picklist\Views;


/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * ********************************************************************************** */

class Index extends \App\Modules\Settings\Base\Views\Index
{

	public function process(\App\Http\Vtiger_Request $request)
	{

		$sourceModule = $request->get('source_module');
		$pickListSupportedModules = \App\Modules\Settings\Picklist\Models\Module::getPicklistSupportedModules();
		if (empty($sourceModule)) {
			//take the first module as the source module
			$sourceModule = $pickListSupportedModules[0]->name;
		}
		$moduleModel = \App\Modules\Settings\Picklist\Models\Module::getInstance($sourceModule);
		$viewer = $this->getViewer($request);
		$qualifiedName = $request->getModule(false);

		$viewer->assign('PICKLIST_MODULES', $pickListSupportedModules);

		$pickListFields = $moduleModel->getFieldsByType(array('picklist', 'multipicklist'));
		if (count($pickListFields) > 0) {
			$selectedPickListFieldModel = reset($pickListFields);

			$selectedFieldAllPickListValues = \App\Fields\Picklist::getPickListValues($selectedPickListFieldModel->getName());


			$viewer->assign('PICKLIST_FIELDS', $pickListFields);
			$viewer->assign('SELECTED_PICKLIST_FIELDMODEL', $selectedPickListFieldModel);
			$viewer->assign('SELECTED_PICKLISTFIELD_ALL_VALUES', $selectedFieldAllPickListValues);
			$viewer->assign('ROLES_LIST', \App\Modules\Settings\Roles\Models\Record::getAll());
		} else {
			$viewer->assign('NO_PICKLIST_FIELDS', true);
			$createPicklistUrl = '';
			$settingsLinks = $moduleModel->getSettingLinks();
			foreach ($settingsLinks as $linkDetails) {
				if ($linkDetails['linklabel'] == 'LBL_EDIT_FIELDS') {
					$createPicklistUrl = $linkDetails['linkurl'];
					break;
				}
			}
			$viewer->assign('CREATE_PICKLIST_URL', $createPicklistUrl);
		}
		$viewer->assign('SELECTED_MODULE_NAME', $sourceModule);
		$viewer->assign('QUALIFIED_NAME', $qualifiedName);

		// Check if this is an AJAX request - if so, return only content without MainLayout
		if ($request->isAjax()) {
			$viewer->view('IndexContent.tpl', $qualifiedName);
		} else {
			$viewer->view('Index.tpl', $qualifiedName);
		}
	}

	public function getFooterScripts(\App\Http\Vtiger_Request $request)
	{
		$headerScriptInstances = parent::getFooterScripts($request);
		$moduleName = $request->getModule();

		$jsFileNames = array(
			"modules.$moduleName.resources.$moduleName",
		);

		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		$headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);
		return $headerScriptInstances;
	}
}
