<?php
/* {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} */



namespace App\Modules\Base\Views;

class Pagination  extends \App\Modules\Base\Views\Basic
{

	public function __construct()
	{
		parent::__construct();
		$this->exposeMethod('getPagination');
		$this->exposeMethod('getRelationPagination');
	}

	public function preProcessAjax(\App\Http\Vtiger_Request $request)
	{
		// Skip MainLayout rendering for AJAX requests
	}

	public function preProcess(\App\Http\Vtiger_Request $request, $display = true)
	{
		// Skip all preProcess - Pagination only renders fragment
	}

	public function postProcess(\App\Http\Vtiger_Request $request)
	{
		// Skip postProcess
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$mode = $request->get('mode');
		if (!empty($mode)) {
			$this->invokeExposedMethod($mode, $request);
			return;
		}
	}

	public function getRelationPagination(\App\Http\Vtiger_Request $request)
	{
		$viewer = $this->getViewer($request);
		$pageNumber = $request->get('page');
		$moduleName = $request->getModule();

		if (empty($pageNumber)) {
			$pageNumber = '1';
		}
		$pagingModel = new \App\Modules\Base\Models\Paging();
		$pagingModel->set('page', $pageNumber);
		$pagingModel->set('noOfEntries', $request->get('noOfEntries'));
		$relatedModuleName = $request->get('relatedModule');
		$parentId = $request->get('record');

		$parentRecordModel = \App\Modules\Base\Models\Record::getInstanceById($parentId, $moduleName);
		$relationListView = \App\Modules\Base\Models\RelationListView::getInstance($parentRecordModel, $relatedModuleName, $label);
		$totalCount = (int) $relationListView->getRelatedEntriesCount();
		if (!empty($totalCount)) {
			$pagingModel->set('totalCount', (int) $totalCount);
		}
		$viewer->assign('LISTVIEW_COUNT', (int) $totalCount);
		$pageCount = $pagingModel->getPageCount();
		$startPaginFrom = $pagingModel->getStartPagingFrom();

		$viewer->assign('PAGE_COUNT', $pageCount);
		$viewer->assign('PAGE_NUMBER', $pageNumber);
		$viewer->assign('START_PAGIN_FROM', $startPaginFrom);
		$viewer->assign('PAGING_MODEL', $pagingModel);
		echo $viewer->view('Pagination.tpl', $moduleName, true);
	}

	public function getPagination(\App\Http\Vtiger_Request $request)
	{
		$viewer = $this->getViewer($request);
		$cvId = $request->get('viewname');
		$pageNumber = $request->get('page');
		$searchResult = $request->get('searchResult');
		$moduleName = $request->getModule();
		if (empty($cvId)) {
			$cvId = \App\View\CustomView::getInstance($moduleName)->getViewId();
		}
		if (empty($pageNumber)) {
			$pageNumber = \App\View\CustomView::getCurrentPage($moduleName, $cvId);
		}
		$pagingModel = new \App\Modules\Base\Models\Paging();
		$pagingModel->set('page', $pageNumber);
		$pagingModel->set('viewid', $cvId);
		$pagingModel->set('noOfEntries', $request->get('noOfEntries'));

		$incomingTotalCount = $request->get('totalCount');
		$totalCount = ($incomingTotalCount === '' || $incomingTotalCount === null) ? null : (int) $incomingTotalCount;
		$listViewModel = \App\Modules\Base\Models\ListView::getInstance($moduleName, $cvId);
		$searchKey = $request->get('search_key');
		$searchValue = $request->get('search_value');
		$operator = $request->get('operator');
		if (!empty($operator)) {
			$listViewModel->set('operator', $operator);
		}
		$hasAlphabetSearch = false;
		if (!empty($searchKey) && $searchValue !== '' && $searchValue !== null) {
			$listViewModel->set('search_key', $searchKey);
			$listViewModel->set('search_value', $searchValue);
			$hasAlphabetSearch = true;
		}
		$searchParmams = $request->get('search_params');
		$hasAdvancedFilters = false;
		if (!empty($searchParmams)) {
			// search_params can come as JSON string from AJAX
			if (is_string($searchParmams)) {
				$searchParmams = json_decode($searchParmams, true);
			}
			if (is_array($searchParmams)) {
				$hasAdvancedFilters = !empty(array_filter($searchParmams));
				$transformedSearchParams = $listViewModel->get('query_generator')->parseBaseSearchParamsToCondition($searchParmams);
				$listViewModel->set('search_params', $transformedSearchParams);
			}
		}
		$shouldComputeTotal = \App\Core\AppConfig::performance('LISTVIEW_COMPUTE_PAGE_COUNT')
			|| $totalCount === null
			|| $totalCount === -1
			|| $hasAdvancedFilters
			|| $hasAlphabetSearch;
		if ($shouldComputeTotal) {
			$totalCount = (int) $listViewModel->getListViewCount();
		}
		if ($totalCount !== null) {
			$pagingModel->set('totalCount', $totalCount);
			if ($totalCount === $pageNumber * $pagingModel->getPageLimit()) {
				$pagingModel->set('nextPageExists', false);
			}
		} else {
			$totalCount = false;
		}
		$viewer->assign('LISTVIEW_COUNT', $totalCount);
		$viewer->assign('TOTAL_ENTRIES', $totalCount);
		$viewer->assign('PAGE_COUNT', $pagingModel->getPageCount());
		$viewer->assign('PAGE_NUMBER', $pageNumber);
		$viewer->assign('START_PAGIN_FROM', $pagingModel->getStartPagingFrom());
		$viewer->assign('PAGING_MODEL', $pagingModel);
		$viewer->view('Pagination.tpl', $moduleName);
	}
}
