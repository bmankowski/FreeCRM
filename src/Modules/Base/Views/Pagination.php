<?php
/* {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} */



namespace App\Modules\Base\Views;

class Pagination  extends \App\Modules\Base\Views\Index
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

	protected function preProcessDisplay(\App\Http\Vtiger_Request $request)
	{
		// Skip template rendering
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
			$cvId = \App\CustomView::getInstance($moduleName)->getViewId();
		}
		if (empty($pageNumber)) {
			$pageNumber = \App\CustomView::getCurrentPage($moduleName, $cvId);
		}
		$pagingModel = new \App\Modules\Base\Models\Paging();
		$pagingModel->set('page', $pageNumber);
		$pagingModel->set('viewid', $cvId);
		$pagingModel->set('noOfEntries', $request->get('noOfEntries'));

		$totalCount = (int) $request->get('totalCount');
		$operator = '';
		if (\App\AppConfig::performance('LISTVIEW_COMPUTE_PAGE_COUNT') || $totalCount == -1) {
			$listViewModel = \App\Modules\Base\Models\ListView::getInstance($moduleName, $cvId);
			$searchKey = $request->get('search_key');
			$searchValue = $request->get('search_value');
			$operator = $request->get('operator');
			if (!empty($operator)) {
				$listViewModel->set('operator', $operator);
			}
			if (!empty($searchKey) && !empty($searchValue)) {
				$listViewModel->set('search_key', $searchKey);
				$listViewModel->set('search_value', $searchValue);
			}
			$searchParmams = $request->get('search_params');
			if (!empty($searchParmams) && is_array($searchParmams)) {
				$transformedSearchParams = $listViewModel->get('query_generator')->parseBaseSearchParamsToCondition($searchParmams);
				$listViewModel->set('search_params', $transformedSearchParams);
			}
			$totalCount = $listViewModel->getListViewCount();
		}
		if (!empty($totalCount)) {
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
