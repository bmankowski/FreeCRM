<?php

namespace App\Modules\CustomView\Actions;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * *********************************************************************************** */

class Save extends \App\Base\Controllers\BaseActionController
{

	public function process(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->get('source_module');
		$moduleModel = \App\Modules\Base\Models\Module::getInstance($moduleName);
		$response = new \App\Http\Vtiger_Response();

		$sortError = null;
		$sort = $this->normalizeSortFromRequest($request, $moduleName, $sortError);
		if ($sortError !== null) {
			$response->setError($sortError);
			$response->emit();
			return;
		}

		$customViewModel = $this->getCVModelFromRequest($request, $sort);

		if (!$customViewModel->checkDuplicate()) {
			$customViewModel->save();
			$cvId = $customViewModel->getId();
			\App\Cache\Cache::delete('\App\Modules\CustomView\Models\RecordgetInstanceById', $cvId);
			$this->syncSessionSortIfCurrentView($moduleName, (int) $cvId, $sort);
			$response->setResult(array('id' => $cvId, 'listviewurl' => $moduleModel->getListViewUrl() . '&viewname=' . $cvId));
		} else {
			$response->setError(\App\Runtime\Vtiger_Language_Handler::translate('LBL_CUSTOM_VIEW_NAME_DUPLICATES_EXIST', $moduleName));
		}

		$response->emit();
	}

	/**
	 * Function to get the custom view model based on the request parameters
	 * @param \App\Http\Vtiger_Request $request
	 * @return \App\Modules\CustomView\Models\Record or Module specific Record Model instance
	 */
	private function getCVModelFromRequest(\App\Http\Vtiger_Request $request, string $sort = '')
	{
		$cvId = $request->get('record');

		if (!empty($cvId)) {
			$customViewModel = \App\Modules\CustomView\Models\Record::getInstanceById($cvId);
		} else {
			$customViewModel = \App\Modules\CustomView\Models\Record::getCleanInstance();
			$customViewModel->setModule($request->get('source_module'));
		}
		$setmetrics = empty($request->get('setmetrics')) ? 0 : $request->get('setmetrics');
		$customViewData = array(
			'cvid' => $cvId,
			'viewname' => $request->get('viewname'),
			'setdefault' => $request->get('setdefault'),
			'setmetrics' => $setmetrics,
			'status' => $request->get('status'),
			'featured' => $request->get('featured'),
			'color' => $request->get('color'),
			'description' => $request->get('description'),
			'sort' => $sort,
		);
		$selectedColumnsList = $request->get('columnslist');
		if (empty($selectedColumnsList)) {
			$moduleModel = \App\Modules\Base\Models\Module::getInstance($request->get('source_module'));
			$cvIdDefault = $moduleModel->getAllFilterCvidForModule();
			if ($cvIdDefault === false) {
				$cvId = \App\View\CustomView::getInstance($request->get('source_module'))->getDefaultCvId();
			}
			$defaultCustomViewModel = \App\Modules\CustomView\Models\Record::getInstanceById($cvIdDefault);
			$selectedColumnsList = $defaultCustomViewModel->getSelectedFields();
		}
		$customViewData['columnslist'] = $selectedColumnsList;
		$stdFilterList = $request->get('stdfilterlist');
		if (!empty($stdFilterList)) {
			$customViewData['stdfilterlist'] = $stdFilterList;
		}
		$advFilterList = $request->get('advfilterlist');
		if (!empty($advFilterList)) {
			$customViewData['advfilterlist'] = $advFilterList;
		}

		return $customViewModel->setData($customViewData);
	}

	private function normalizeSortFromRequest(
		\App\Http\Vtiger_Request $request,
		string $moduleName,
		?string &$error
	): string {
		$orderBy = trim((string) $request->get('defaultOrderBy', ''));
		if ($orderBy === '') {
			return '';
		}
		$moduleModel = \App\Modules\Base\Models\Module::getInstance($moduleName);
		$field = $moduleModel->getFieldByColumn($orderBy);
		if (!$field || !$field->isListviewSortable()) {
			$error = \App\Runtime\Vtiger_Language_Handler::translate('LBL_INVALID_SORT_FIELD', 'CustomView');
			return '';
		}
		$formatted = \App\Modules\CustomView\Models\Record::formatSortValue($orderBy, $request->get('sortOrder'));
		if (strlen($formatted) > 30) {
			$error = \App\Runtime\Vtiger_Language_Handler::translate('LBL_INVALID_SORT_FIELD', 'CustomView');
			return '';
		}
		return $formatted;
	}

	private function syncSessionSortIfCurrentView(string $moduleName, int $cvId, string $sort): void
	{
		if (\App\View\CustomView::getCurrentView($moduleName) !== $cvId) {
			return;
		}
		$parsed = \App\Modules\CustomView\Models\Record::parseSortValue($sort);
		\App\View\CustomView::setSortby($moduleName, $parsed['orderBy']);
		\App\View\CustomView::setSorder($moduleName, $parsed['sortOrder']);
	}

	public function validateRequest(\App\Http\Vtiger_Request $request)
	{
		$request->validateWriteAccess();
	}
}
