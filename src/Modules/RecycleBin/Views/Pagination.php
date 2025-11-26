<?php
/* {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} */



namespace App\Modules\RecycleBin\Views;

use App\Http\Vtiger_Request;
class Pagination  extends \App\Modules\Base\Views\Index
{
	protected $listViewHeaders = [];
	protected $listViewEntries = [];
	protected $listViewCount = 0;

	public function __construct()
	{
		parent::__construct();
		$this->exposeMethod('getPagination');
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

	public function getPagination(\App\Http\Vtiger_Request $request)
	{
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		$sourceModule = $request->get('sourceModule');

		$pageNumber = $request->get('page');
		$orderBy = $request->get('orderby');
		$sortOrder = $request->get('sortorder');
		if ($sortOrder == 'ASC') {
			$nextSortOrder = 'DESC';
			$sortImage = 'glyphicon glyphicon-chevron-down';
		} else {
			$nextSortOrder = 'ASC';
			$sortImage = 'glyphicon glyphicon-chevron-up';
		}

		if (empty($pageNumber)) {
			$pageNumber = '1';
		}

		$moduleModel = \App\Modules\RecycleBin\Models\Module::getInstance($moduleName);
		//If sourceModule is empty, pick the first module name from the list
		if (empty($sourceModule)) {
			foreach ($moduleModel->getAllModuleList() as $model) {
				$sourceModule = $model->get('name');
				break;
			}
		}
		$listViewModel = \App\Modules\RecycleBin\Models\ListView::getInstance($moduleName, $sourceModule);

		$linkParams = array('MODULE' => $moduleName, 'ACTION' => $request->get('view'));
		$linkModels = $moduleModel->getListViewMassActions($linkParams);

		$pagingModel = new \App\Modules\Base\Models\Paging();
		$pagingModel->set('page', $pageNumber);
		if (empty($orderBy) && empty($sortOrder)) {
			$moduleInstance = \App\Core\CRMEntity::getInstance($moduleName);
			$orderBy = isset($moduleInstance->default_order_by) ? $moduleInstance->default_order_by : 'modifiedtime';
			$sortOrder = isset($moduleInstance->default_sort_order) ? $moduleInstance->default_sort_order : 'DESC';
		}
		if (!empty($orderBy)) {
			$listViewModel->set('orderby', $orderBy);
			$listViewModel->set('sortorder', $sortOrder);
		}

		// Handle search parameters
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
		$searchParams = $request->get('search_params');
		if (!empty($searchParams)) {
			// search_params can come as JSON string from AJAX
			if (is_string($searchParams)) {
				$searchParams = json_decode($searchParams, true);
			}
			if (is_array($searchParams)) {
				$transformedSearchParams = $listViewModel->get('query_generator')->parseBaseSearchParamsToCondition($searchParams);
				$listViewModel->set('search_params', $transformedSearchParams);
			}
		}

		if (empty($this->listViewHeaders)) {
			$this->listViewHeaders = $listViewModel->getListViewHeaders();
		}
		if (empty($this->listViewEntries)) {
			$this->listViewEntries = $listViewModel->getListViewEntries($pagingModel);
		}
		$noOfEntries = is_array($this->listViewEntries) ? count($this->listViewEntries) : 0;

		$viewer->assign('MODULE', $moduleName);

		$viewer->assign('LISTVIEW_LINKS', $moduleModel->getListViewLinks(false));
		$viewer->assign('LISTVIEW_MASSACTIONS', $linkModels);

		$viewer->assign('PAGING_MODEL', $pagingModel);
		$viewer->assign('PAGE_NUMBER', $pageNumber);

		$viewer->assign('ORDER_BY', $orderBy);
		$viewer->assign('SORT_ORDER', $sortOrder);
		$viewer->assign('NEXT_SORT_ORDER', $nextSortOrder);
		$viewer->assign('SORT_IMAGE', $sortImage);
		$viewer->assign('COLUMN_NAME', $orderBy);

		$viewer->assign('LISTVIEW_ENTRIES_COUNT', $noOfEntries);
		$viewer->assign('LISTVIEW_HEADERS', $this->listViewHeaders);
		$viewer->assign('LISTVIEW_ENTRIES', $this->listViewEntries);
		$viewer->assign('MODULE_LIST', $moduleModel->getAllModuleList());
		$viewer->assign('SOURCE_MODULE', $sourceModule);
		$viewer->assign('DELETED_RECORDS_TOTAL_COUNT', $moduleModel->getDeletedRecordsTotalCount());


		if (!$this->listViewCount) {
			$this->listViewCount = $listViewModel->getListViewCount();
		}
		$totalCount = $this->listViewCount;
		$pagingModel->set('totalCount', (int) $totalCount);
		$pageCount = $pagingModel->getPageCount();
		$startPaginFrom = $pagingModel->getStartPagingFrom();

		$viewer->assign('PAGE_COUNT', $pageCount);
		$viewer->assign('LISTVIEW_COUNT', $totalCount);
		$viewer->assign('START_PAGIN_FROM', $startPaginFrom);
		$viewer->assign('IS_MODULE_DELETABLE', $listViewModel->getModule()->isPermitted('Delete'));
		echo $viewer->view('Pagination.tpl', $moduleName, true);
	}

}
