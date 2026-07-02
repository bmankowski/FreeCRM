<?php

namespace App\Modules\Base\Actions;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

class BasicAjax extends \App\Base\Controllers\BaseActionController
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

		$searchModuleModel = \App\Modules\Base\Models\Module::getInstance($searchModule);
		$records = $searchModuleModel->searchRecord($searchValue, $parentRecordId, $parentModuleName, $relatedModule);

		$result = [];
		if (is_array($records)) {
			foreach ($records as $moduleName => $recordModels) {
				foreach ($recordModels as $recordModel) {
					$searchLabel = \App\Utils\ListViewUtils::decodeHtml($recordModel->getSearchName());
					$recordName = \App\Utils\ListViewUtils::decodeHtml($recordModel->getName());
					$recordModule = $recordModel->getModuleName();
					$result[] = [
						'id' => $recordModel->getId(),
						'label' => $searchLabel,
						'value' => $recordName,
						'subtitle' => ($searchLabel !== $recordName) ? $searchLabel : '',
						'module' => $recordModule,
						'moduleLabel' => \App\Runtime\Vtiger_Language_Handler::translate($recordModule, $recordModule),
					];
				}
			}
		}

		$response = new \App\Http\Vtiger_Response();
		$response->setResult($result);
		$response->emit();
	}
}
