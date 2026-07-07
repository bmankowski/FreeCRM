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

		$customViewModel->save();
		$cvId = $customViewModel->getId();
		\App\Cache\Cache::delete('\App\Modules\CustomView\Models\RecordgetInstanceById', $cvId);
		$this->syncSessionSortIfCurrentView($moduleName, (int) $cvId, $sort);
		$listViewUrl = $moduleModel->getListViewUrl() . '&viewname=' . $cvId;

		if (!$request->isAjax()) {
			header('Location: ' . $listViewUrl);
			return;
		}

		$response->setResult(array('id' => $cvId, 'listviewurl' => $listViewUrl));

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
		$isEdit = !empty($cvId);
		$setmetrics = empty($request->get('setmetrics')) ? 0 : $request->get('setmetrics');
		$customViewData = array(
			'cvid' => $cvId,
			'viewname' => $request->get('viewname'),
			'setdefault' => $request->get('setdefault'),
			'setmetrics' => $setmetrics,
			'status' => $request->get('status'),
			'featured' => $request->get('featured'),
			'color' => $this->normalizeColorFromRequest($request, $customViewModel, $isEdit),
			'description' => $request->get('description'),
			'sort' => $sort,
		);
		$selectedColumnsList = $request->get('columnslist');
		if (empty($selectedColumnsList)) {
			if ($isEdit) {
				$selectedColumnsList = $customViewModel->getSelectedFields();
			} else {
				$moduleModel = \App\Modules\Base\Models\Module::getInstance($request->get('source_module'));
				$cvIdDefault = $moduleModel->getAllFilterCvidForModule();
				if ($cvIdDefault === false) {
					$cvId = \App\View\CustomView::getInstance($request->get('source_module'))->getDefaultCvId();
				}
				$defaultCustomViewModel = \App\Modules\CustomView\Models\Record::getInstanceById($cvIdDefault);
				$selectedColumnsList = $defaultCustomViewModel->getSelectedFields();
			}
		}
		$customViewData['columnslist'] = $selectedColumnsList;

		$stdFilterListRaw = $request->getRaw('stdfilterlist', '');
		$advFilterListRaw = $request->getRaw('advfilterlist', '');
		if ($stdFilterListRaw !== '' && $stdFilterListRaw !== null) {
			$stdFilterList = $request->get('stdfilterlist');
			if (!empty($stdFilterList)) {
				$customViewData['stdfilterlist'] = $stdFilterList;
			}
		} elseif ($isEdit && ($advFilterListRaw === '' || $advFilterListRaw === null)) {
			$stdFilterList = $customViewModel->getStandardCriteria();
			if (!empty($stdFilterList)) {
				$customViewData['stdfilterlist'] = $stdFilterList;
			}
		}

		if ($advFilterListRaw !== '' && $advFilterListRaw !== null) {
			$customViewData['advfilterlist'] = $request->get('advfilterlist');
		} elseif ($isEdit) {
			$customViewData['advfilterlist'] = $customViewModel->transformToNewAdvancedFilter();
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

	private function normalizeColorFromRequest(
		\App\Http\Vtiger_Request $request,
		\App\Modules\CustomView\Models\Record $customViewModel,
		bool $isEdit
	): string {
		$color = trim((string) $request->get('color', ''));
		if ($color !== '') {
			return $color;
		}
		if ($isEdit) {
			$existing = trim((string) $customViewModel->get('color'));
			if ($existing !== '') {
				return $existing;
			}
		}
		return '#ffffff';
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
