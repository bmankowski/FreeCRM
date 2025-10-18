<?php

namespace App\Modules\Vtiger\Actions;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

class BasicAjax extends \App\Runtime\Vtiger_Action_Controller
{

	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		return;
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$searchValue = $request->get('search_value');
		$searchModule = $request->get('search_module');

		$parentRecordId = $request->get('parent_id');
		$parentModuleName = $request->get('parent_module');
		$relatedModule = $request->get('module');

		$searchModuleModel = \App\Modules\Vtiger\Models\Module::getInstance($searchModule);
		$records = $searchModuleModel->searchRecord($searchValue, $parentRecordId, $parentModuleName, $relatedModule);

		$result = [];
		if (is_array($records)) {
			foreach ($records as $moduleName => $recordModels) {
				foreach ($recordModels as $recordModel) {
					$result[] = array('label' => \App\Utils\ListViewUtils::decodeHtml($recordModel->getSearchName()), 'value' => \App\Utils\ListViewUtils::decodeHtml($recordModel->getName()), 'id' => $recordModel->getId());
				}
			}
		}

		$response = new \App\Http\Vtiger_Response();
		$response->setResult($result);
		$response->emit();
	}
}
