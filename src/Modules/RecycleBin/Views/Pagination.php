<?php
/* {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} */



namespace App\Modules\RecycleBin\Views;

class Pagination extends \App\Modules\Base\Views\Basic
{
	protected $listViewCount = 0;

	public function __construct()
	{
		parent::__construct();
		$this->exposeMethod('getPagination');
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

		$viewer->assign('PAGING_MODEL', $pagingModel);
		$viewer->assign('PAGE_NUMBER', $pageNumber);


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
		echo $viewer->view('Pagination.tpl', $moduleName, true);
	}

}
