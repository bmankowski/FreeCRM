<?php

namespace App\Modules\Settings\Users\Views;

/**
 * Settings Users ListView Class
 * Delegates to the main Users ListView
 */
class ListView extends \App\Modules\Settings\Base\Views\ListView
{
	protected $listViewModel;

	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		$currentUserModel = $request->getUser();
		if (!$currentUserModel->isAdminUser()) {
			throw new \App\Exceptions\NoPermitted('LBL_PERMISSION_DENIED');
		}
	}
	
	public function initializeListViewContents(\App\Http\Vtiger_Request $request, \App\Runtime\CRM_Viewer $viewer)
	{
		$moduleName = $request->getModule();
		$cvId = $request->get('viewname');
		$pageNumber = $request->get('page');
		$orderBy = $request->get('orderby');
		$sortOrder = $request->get('sortorder');
		$searchResult = $request->get('searchResult');
		if (empty($orderBy) && empty($sortOrder)) {
			$moduleInstance = \App\CRMEntity::getInstance($moduleName);
			$orderBy = $moduleInstance->default_order_by;
			$sortOrder = $moduleInstance->default_sort_order;
		}
		if ($sortOrder == "ASC") {
			$nextSortOrder = "DESC";
			$sortImage = "glyphicon glyphicon-chevron-down";
		} else {
			$nextSortOrder = "ASC";
			$sortImage = "glyphicon glyphicon-chevron-up";
		}

		if (empty($pageNumber)) {
			$pageNumber = '1';
		}

		$status = $request->get('status');
		if (empty($status))
			$status = 'Active';

		if (!$this->listViewModel) {
			$this->listViewModel = \App\Modules\Users\Models\ListView::getInstance($moduleName, $cvId);
		}

		$linkParams = array('MODULE' => $moduleName, 'ACTION' => $request->get('view'), 'CVID' => $cvId);
		$linkModels = $this->listViewModel->getListViewMassActions($linkParams);
		// Ensure LISTVIEWMASSACTION is always an array to prevent template errors
		if (!isset($linkModels['LISTVIEWMASSACTION'])) {
			$linkModels['LISTVIEWMASSACTION'] = [];
		}
		$this->listViewModel->set('status', $status);

		$pagingModel = new \App\Modules\Base\Models\Paging();
		$pagingModel->set('page', $pageNumber);
		$pagingModel->set('viewid', $cvId);

		if (!empty($orderBy)) {
			$this->listViewModel->set('orderby', $orderBy);
			$this->listViewModel->set('sortorder', $sortOrder);
		}

		$searchKey = $request->get('search_key');
		$searchValue = $request->get('search_value');
		$operator = $request->get('operator');
		if (!empty($operator)) {
			$this->listViewModel->set('operator', $operator);
		}
		$viewer->assign('OPERATOR', $operator);
		if ('status' != $searchKey)
			$viewer->assign('ALPHABET_VALUE', $searchValue);
		if (!empty($searchKey) && !empty($searchValue)) {
			$this->listViewModel->set('search_key', $searchKey);
			$this->listViewModel->set('search_value', $searchValue);
		}

		$searchParmams = $request->get('search_params');
		if (empty($searchParmams) || !is_array($searchParmams)) {
			$searchParmams = array();
		}
		$transformedSearchParams = $this->listViewModel->get('query_generator')->parseBaseSearchParamsToCondition($searchParmams);
		$this->listViewModel->set('search_params', $transformedSearchParams);

		//To make smarty to get the details easily accesible
		foreach ($searchParmams as $fieldListGroup) {
			foreach ($fieldListGroup as $fieldSearchInfo) {
				$fieldSearchInfo['searchValue'] = $fieldSearchInfo[2];
				$fieldSearchInfo['fieldName'] = $fieldName = $fieldSearchInfo[0];
				$fieldSearchInfo['specialOption'] = $fieldSearchInfo[3];
				$searchParmams[$fieldName] = $fieldSearchInfo;
			}
		}
		if (!empty($searchResult) && is_array($searchResult)) {
			$this->listViewModel->get('query_generator')->addNativeCondition(['vtiger_crmentity.crmid' => $searchResult]);
		}
		if (!$this->listViewHeaders) {
			$this->listViewHeaders = $this->listViewModel->getListViewHeaders();
		}
		if (!$this->listViewEntries) {
			$this->listViewEntries = $this->listViewModel->getListViewEntries($pagingModel);
		}
		$noOfEntries = count($this->listViewEntries);

		$viewer->assign('MODULE', $moduleName);

		if (!isset($this->listViewLinks)) {
			$this->listViewLinks = $this->listViewModel->getListViewLinks($linkParams);
		}
		// Ensure LISTVIEW_LINKS is always an array with required keys to prevent template errors
		if (!is_array($this->listViewLinks)) {
			$this->listViewLinks = [];
		}
		if (!isset($this->listViewLinks['LISTVIEW'])) {
			$this->listViewLinks['LISTVIEW'] = [];
		}
		if (!isset($this->listViewLinks['LISTVIEWBASIC'])) {
			$this->listViewLinks['LISTVIEWBASIC'] = [];
		}
		$viewer->assign('LISTVIEW_LINKS', $this->listViewLinks);
		$viewer->assign('LISTVIEW_MASSACTIONS', $linkModels['LISTVIEWMASSACTION']);
		$viewer->assign('PAGING_MODEL', $pagingModel);
		$viewer->assign('PAGE_NUMBER', $pageNumber);
		$viewer->assign('ORDER_BY', $orderBy);
		$viewer->assign('SORT_ORDER', $sortOrder);
		$viewer->assign('NEXT_SORT_ORDER', $nextSortOrder);
		$viewer->assign('SORT_IMAGE', $sortImage);
		$viewer->assign('COLUMN_NAME', $orderBy);
		$viewer->assign('QUALIFIED_MODULE', $moduleName);
		$viewer->assign('LISTVIEW_ENTRIES_COUNT', $noOfEntries);
		$viewer->assign('LISTVIEW_HEADERS', $this->listViewHeaders);
		$viewer->assign('LISTVIEW_ENTRIES', $this->listViewEntries);

		if (\App\AppConfig::performance('LISTVIEW_COMPUTE_PAGE_COUNT')) {
			if (!$this->listViewCount) {
				$this->listViewCount = $this->listViewModel->getListViewCount();
			}
			$pagingModel->set('totalCount', (int) $this->listViewCount);
			$viewer->assign('LISTVIEW_COUNT', $this->listViewCount);
		} else {
			// Assign default to prevent template warnings
			$viewer->assign('LISTVIEW_COUNT', $noOfEntries);
		}
		$pageCount = $pagingModel->getPageCount();
		$startPaginFrom = $pagingModel->getStartPagingFrom();

		$viewer->assign('PAGE_COUNT', $pageCount);
		$viewer->assign('START_PAGIN_FROM', $startPaginFrom);
		$viewer->assign('MODULE_MODEL', $this->listViewModel->getModule());
		$viewer->assign('IS_MODULE_EDITABLE', $this->listViewModel->getModule()->isPermitted('EditView'));
		$viewer->assign('IS_MODULE_DELETABLE', $this->listViewModel->getModule()->isPermitted('Delete'));
		$viewer->assign('USER_MODEL', $request->getUser());
		$viewer->assign('SEARCH_DETAILS', $searchParmams);
	}

	public function getFooterScripts(\App\Http\Vtiger_Request $request)
	{
		$headerScriptInstances = parent::getFooterScripts($request);
		$jsFileNames = [
			'modules.Base.resources.ListView',
			'modules.Users.resources.List',
		];
		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		$headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);
		return $headerScriptInstances;
	}
}

