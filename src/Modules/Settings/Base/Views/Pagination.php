<?php

namespace App\Modules\Settings\Base\Views;


/* {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} */


class Pagination extends \App\Modules\Settings\Base\Views\IndexAjax
{
	/** @var array<int|string, \App\Modules\Settings\Base\Models\Record>|null */
	protected $listViewEntries = null;

	/** @var int|null */
	protected $listViewCount;

	public function __construct()
	{
		parent::__construct();
		$this->exposeMethod('getPagination');
	}

	public function getPagination(\App\Http\Vtiger_Request $request)
	{
		$viewer = $this->getViewer($request);
		$pageNumber = $request->get('page');
		$searchResult = $request->get('searchResult');
		$qualifiedModuleName = $request->getModule(false);
		$sourceModule = $request->get('sourceModule');
		$forModule = $request->get('formodule');
		/** @var \App\Modules\Settings\Base\Models\ListView $listViewModel */
		$listViewModel = \App\Modules\Settings\Base\Models\ListView::getInstance($qualifiedModuleName);
		if (empty($pageNumber)) {
			$pageNumber = '1';
		}
		if (!empty($sourceModule)) {
			$listViewModel->set('sourceModule', $sourceModule);
		}
		if (!empty($forModule)) {
			$listViewModel->set('formodule', $forModule);
		}

		$pagingModel = new \App\Modules\Base\Models\Paging();
		$pagingModel->set('page', $pageNumber);
		$pagingModel->set('viewid', $request->get('viewname'));
		$searchKey = $request->get('search_key');
		$searchValue = $request->get('search_value');
		$operator = $request->get('operator');
		if (!empty($operator)) {
			$listViewModel->set('operator', $operator);
			$viewer->assign('OPERATOR', $operator);
			$viewer->assign('ALPHABET_VALUE', $searchValue);
		}
		if (!empty($searchKey) && !empty($searchValue)) {
			$listViewModel->set('search_key', $searchKey);
			$listViewModel->set('search_value', $searchValue);
		}

		$searchParmams = $request->get('search_params');
		if (empty($searchParmams) || !is_array($searchParmams)) {
			$searchParmams = [];
		}
		$transformedSearchParams = $this->transferListSearchParamsToFilterCondition($searchParmams, $listViewModel->getModule());
		$listViewModel->set('search_params', $transformedSearchParams);
		if (!empty($searchResult) && is_array($searchResult)) {
			$listViewModel->get('query_generator')->addNativeCondition(['vtiger_crmentity.crmid' => $searchResult]);
		}
		if (empty($this->listViewEntries)) {
			$this->listViewEntries = $listViewModel->getListViewEntries($pagingModel);
		}
		if (empty($this->listViewCount)) {
			$this->listViewCount = $listViewModel->getListViewCount();
		}
		$noOfEntries = count($this->listViewEntries);
		$totalCount = $this->listViewCount;
		$pagingModel->set('totalCount', (int) $totalCount);
		$pageCount = $pagingModel->getPageCount();
		$startPaginFrom = $pagingModel->getStartPagingFrom();

		$viewer->assign('PAGE_COUNT', $pageCount);
		$viewer->assign('LISTVIEW_ENTRIES_COUNT', $noOfEntries);
		$viewer->assign('PAGE_NUMBER', $pageNumber);
		$viewer->assign('LISTVIEW_COUNT', $totalCount);
		$viewer->assign('START_PAGIN_FROM', $startPaginFrom);
		$viewer->assign('PAGING_MODEL', $pagingModel);
		echo $viewer->view('Pagination.tpl', $qualifiedModuleName, true);
	}

	/**
	 * @param array<int|string, mixed> $listSearchParams
	 * @param \App\Modules\Base\Models\Module|\App\Modules\Settings\Base\Models\Module $moduleModel
	 * @return array<int|string, mixed>
	 */
	public function transferListSearchParamsToFilterCondition(array $listSearchParams, $moduleModel): array
	{
		return \App\Modules\Base\Helpers\Util::transferListSearchParamsToFilterCondition($listSearchParams, $moduleModel);
	}
}
