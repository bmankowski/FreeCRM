<?php
/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * *********************************************************************************** */

abstract class Mass extends \App\Runtime\BaseActionController
{

	public static function getRecordsListFromRequest(\App\Http\Vtiger_Request $request)
	{
		$cvId = $request->get('viewname');
		$module = $request->get('module');
		if (!empty($cvId) && $cvId == "undefined" && $request->get('source_module') != 'Users') {
			$sourceModule = $request->get('sourceModule');
			$cvId = \App\Modules\CustomView\Models\Record::getAllFilterByModule($sourceModule)->getId();
		}
		$selectedIds = $request->get('selected_ids');
		$excludedIds = $request->get('excluded_ids');

		if (!empty($selectedIds) && !in_array($selectedIds, ['all', '"all"'])) {
			if (!empty($selectedIds) && count($selectedIds) > 0) {
				return $selectedIds;
			}
		}

		$customViewModel = \App\Modules\CustomView\Models\Record::getInstanceById($cvId);
		if ($customViewModel) {
			$searchKey = $request->get('search_key');
			$searchValue = $request->get('search_value');
			$operator = $request->get('operator');
			if (!empty($operator)) {
				$customViewModel->set('operator', $operator);
				$customViewModel->set('search_key', $searchKey);
				$customViewModel->set('search_value', $searchValue);
			}

			$customViewModel->set('search_params', $request->get('search_params'));
			return $customViewModel->getRecordIds($excludedIds, $module);
		}
	}

	public function validateRequest(\App\Http\Vtiger_Request $request)
	{
		$request->validateWriteAccess();
	}
}
