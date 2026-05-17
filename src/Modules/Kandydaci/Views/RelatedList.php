<?php

namespace App\Modules\Kandydaci\Views;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce S.A.
 * *********************************************************************************** */

class RelatedList extends \App\Modules\Base\Views\RelatedList
{

	/**
	 * Process.
	 *
	 * @param \App\Request $request
	 */
	public function process(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$relatedModuleName = $request->getByType('relatedModule', 2);
		$parentId = $request->getInteger('record');
		if ($request->isEmpty('relatedView', true)) {
			$relatedView = empty($_SESSION['relatedView'][$moduleName][$relatedModuleName]) ? 'List' : $_SESSION['relatedView'][$moduleName][$relatedModuleName];
		} else {
			$relatedView = $request->getByType('relatedView');
			$_SESSION['relatedView'][$moduleName][$relatedModuleName] = $relatedView;
		}
		$pageNumber = $request->isEmpty('page', true) ? 1 : $request->getInteger('page');
		$totalCount = $request->isEmpty('totalCount', true) ? 0 : $request->getInteger('totalCount');
		$pagingModel = new \App\Modules\Base\Models\Paging();
		$pagingModel->set('page', $pageNumber);
		if ($request->has('limit')) {
			$pagingModel->set('limit', $request->getInteger('limit'));
		}
		$cvId = $request->isEmpty('cvId', true) ? 0 : $request->getByType('cvId', 'Alnum');
		$parentRecordModel = \App\Modules\Base\Models\Record::getInstanceById($parentId, $moduleName);
		$relationListView = \App\Modules\Base\Models\RelationListView::getInstance($parentRecordModel, $relatedModuleName, $request->getInteger('relationId'), $cvId);

		$orderBy = $request->get('orderby');
		$sortOrder = $request->get('sortorder');
		if ($sortOrder == 'ASC') {
			$nextSortOrder = 'DESC';
			$sortImage = 'glyphicon glyphicon-chevron-down';
		} else {
			$nextSortOrder = 'ASC';
			$sortImage = 'glyphicon glyphicon-chevron-up';
		}
		if (empty($orderBy) && empty($sortOrder)) {
			$moduleInstance = $relationListView->getRelatedModuleModel()->getEntityInstance();
			$orderBy = $moduleInstance->default_order_by;
			$sortOrder = $moduleInstance->default_sort_order;
		}
		if (!empty($orderBy)) {
			$relationListView->set('orderby', $orderBy);
			$relationListView->set('sortorder', $sortOrder);
		}

		$columnName = \is_array($orderBy) ? (string) array_key_first($orderBy) : (string) $orderBy;

		if ($request->has('entityState')) {
			$relationListView->set('entityState', $request->getByType('entityState'));
		}
		$viewer = $this->getViewer($request);
		$viewer->assign('SORT_ORDER', $sortOrder);
		$viewer->assign('NEXT_SORT_ORDER', $nextSortOrder);
		$viewer->assign('SORT_IMAGE', $sortImage);
		$viewer->assign('COLUMN_NAME', $columnName);
		$operator = 's';
		if (!$request->isEmpty('operator', true)) {
			$operator = $request->getByType('operator', 'Alnum');
			$relationListView->set('operator', $operator);
			$viewer->assign('OPERATOR', $operator);
		}
		if (!$request->isEmpty('search_key', true)) {
			$searchKey = $request->getByType('search_key', 'Alnum');
			$searchValue = $request->getByType('search_value', 'Text');
			$relationListView->set('search_key', $searchKey);
			$relationListView->set('search_value', $searchValue);
			$viewer->assign('ALPHABET_VALUE', $searchValue);
		}
		$searchParams = $request->getArray('search_params');
		if (empty($searchParams) || !\is_array($searchParams)) {
			$searchParamsRaw = $searchParams = [];
		}
		$queryGenerator = $relationListView->getQueryGenerator();
		$transformedSearchParams = $queryGenerator->parseBaseSearchParamsToCondition($searchParams);
		$relationListView->set('search_params', $transformedSearchParams);
		// if (\method_exists($relationListView, 'loadSearchLockedFields')) {
		// 	$relationListView->loadSearchLockedFields($request);
		// }
		//To make smarty to get the details easily accesible
		foreach ($request->getArray('search_params') as $fieldListGroup) {
			$searchParamsRaw[] = $fieldListGroup;
			foreach ($fieldListGroup as $fieldSearchInfo) {
				$fieldSearchInfo['searchValue'] = $fieldSearchInfo[2] ?? '';
				$fieldSearchInfo['fieldName'] = $fieldName = $fieldSearchInfo[0] ?? '';
				$fieldSearchInfo['specialOption'] = $fieldSearchInfo[3] ?? '';
				$searchParams[$fieldName] = $fieldSearchInfo;
			}
		}
		$showHeader = true;
		if ($request->has('showHeader')) {
			$showHeader = $request->getBoolean('showHeader');
		}
		if ($showHeader) {
			$links = $relationListView->getLinks();
			if (!($request->has('showViews') ? $request->getBoolean('showViews') : true)) {
				unset($links['RELATEDLIST_VIEWS']);
				$relatedView = 'List';
			}
			if (!($request->has('showMassActions') ? $request->getBoolean('showMassActions') : true)) {
				unset($links['RELATEDLIST_MASSACTIONS']);
			}
			$viewer->assign('RELATED_LIST_LINKS', $links);
		}
		if ('ListPreview' === $relatedView) {
			if (\method_exists($relationListView, 'setFields')) {
				$relationListView->setFields(array_merge(['id'], $relationListView->getRelatedModuleModel()->getNameFields()));
			}
		}
		if ($request->has('fields')) {
			if (\method_exists($relationListView, 'setFields')) {
				$relationListView->setFields(array_merge(['id'], $request->getArray('fields', 'Alnum')));
			}
		}
		if ($request->has('quickSearchEnabled')) {
			$relationListView->set('quickSearchEnabled', $request->getBoolean('quickSearchEnabled'));
		}
		$models = $relationListView->getEntries($pagingModel);
		$header = $relationListView->getHeaders();
		$relationModel = $relationListView->getRelationModel();
		// Ensure search details exist for all headers to avoid undefined index notices in templates
		if (\is_array($header)) {
			foreach ($header as $headerField) {
				$headerName = $headerField->getName();
				if (!isset($searchParams[$headerName])) {
					$searchParams[$headerName] = ['searchValue' => '', 'fieldName' => $headerName];
				}
			}
		}
		if ($request->has('sortEnabled')) {
			$relationListView->set('advSortEnabled', $request->getBoolean('sortEnabled'));
		}
		$viewer->assign('VIEW_MODEL', $relationListView);
		$viewer->assign('RELATED_RECORDS', $models);
		$viewer->assign('PARENT_RECORD', $parentRecordModel);
		$viewer->assign('RELATED_VIEW', $relatedView);
		$viewer->assign('SHOW_SUMMATION_ROW', $request->has('showSummation') ? $request->getBoolean('showSummation') : true);
		$viewer->assign('SHOW_HEADER', $showHeader);
		$viewer->assign('SHOW_CREATOR_DETAIL', $relationModel->showCreatorDetail());
		$viewer->assign('SHOW_COMMENT', $relationModel->showComment());
		$viewer->assign('RELATED_HEADERS', $header);
		$viewer->assign('RELATED_MODULE', $relationModel->getRelationModuleModel());
		$viewer->assign('RELATED_MODULE_NAME', $relatedModuleName);
		$viewer->assign('RELATED_ENTIRES_COUNT', \count($models));
		$viewer->assign('RELATION_FIELD', $relationModel->getRelationField());
		if (\App\Core\AppConfig::performance('LISTVIEW_COMPUTE_PAGE_COUNT')) {
			$totalCount = (int) $relationListView->getRelatedEntriesCount();
			$pagingModel->set('totalCount', $totalCount);
		} elseif (!empty($totalCount)) {
			$pagingModel->set('totalCount', $totalCount);
		}
		$viewer->assign('LISTVIEW_COUNT', $totalCount);
		$viewer->assign('TOTAL_ENTRIES', $totalCount);
		$viewer->assign('PAGE_COUNT', $pagingModel->getPageCount());
		$viewer->assign('PAGE_NUMBER', $pageNumber);
		$viewer->assign('START_PAGIN_FROM', $pagingModel->getStartPagingFrom());
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('PAGING_MODEL', $pagingModel);
		$viewer->assign('ORDER_BY', $orderBy);
		$viewer->assign('INVENTORY_FIELDS', $relationModel->getRelationInventoryFields());
		$isFavorites = false;
		if ($relationModel->isFavorites() && \App\Security\Privilege::isPermitted($moduleName, 'FavoriteRecords')) {
			$favorites = $relationListView->getFavoriteRecords();
			$viewer->assign('FAVORITES', $favorites);
			$isFavorites = $relationModel->isFavorites();
		}
		$viewer->assign('IS_FAVORITES', $isFavorites);
		$viewer->assign('IS_EDITABLE', $relationModel->isEditable());
		$viewer->assign('IS_DELETABLE', $relationModel->isDeletable());
		$viewer->assign('USER_MODEL', $request->getUser());
		$viewer->assign('IS_CREATE_PERMITTED', \App\Modules\Users\Models\Privileges::isPermitted($relatedModuleName, 'CreateView'));
		$viewer->assign('SEARCH_DETAILS', $searchParams);
		$viewer->assign('SEARCH_PARAMS', $searchParamsRaw);
		$viewer->assign('VIEW', $request->getByType('view'));
		$showRelatedWidgetsByDefault = \App\Core\AppConfig::module($moduleName, 'showRelatedWidgetsByDefault', []);
		$viewer->assign('SHOW_RELATED_WIDGETS', \in_array($relationModel->getId(), \is_array($showRelatedWidgetsByDefault) ? $showRelatedWidgetsByDefault : []));
		$viewer->assign('LOCKED_EMPTY_FIELDS', $request->isEmpty('lockedEmptyFields', true) ? [] : $request->getArray('lockedEmptyFields'));
		if (\method_exists($relationListView, 'isWidgetsList') && $relationListView->isWidgetsList() && \class_exists(\App\ModuleHierarchy::class) && \class_exists(\Config\Modules\ModComments::class)) {
			$viewer->assign('IS_WIDGETS', true);
			$viewer->assign('HIERARCHY_VALUE', \Config\Modules\ModComments::$defaultSource);
			$viewer->assign('HIERARCHY', \App\ModuleHierarchy::getModuleLevel($relatedModuleName));
		} else {
			$viewer->assign('IS_WIDGETS', false);
		}
		// Prepare data for RelatedListLeftSide template - move function calls from templates to controller
		$relatedModuleModel = $relationModel->getRelationModuleModel();
		$this->prepareRelatedListLeftSideData($viewer, $models, $relatedModuleModel, $request->getUser(), $parentRecordModel, $relationModel->isEditable(), $relationModel->isDeletable());

		// Prepare data for RelatedList template - move function calls from templates to controller
		$viewer->assign('AUTO_REFRESH_LIST_ON_CHANGE', \App\Core\AppConfig::performance('AUTO_REFRESH_RECORD_LIST_ON_SELECT_CHANGE'));

		return $viewer->view('RelatedList.tpl', $moduleName, true);
	}

}
