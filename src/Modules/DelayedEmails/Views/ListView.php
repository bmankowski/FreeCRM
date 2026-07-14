<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * @project FreeCRM
 * @author bmankowski@gmail.com
 * @copyright (c) FreeCRM
 * @license FreeCRM Public License 1.1
 */

declare(strict_types=1);

namespace App\Modules\DelayedEmails\Views;

class ListView extends \App\Modules\Base\Views\Index
{
	protected $listViewEntries = null;
	protected $listViewHeaders = null;
	protected $listViewModel = null;
	protected $listViewLinks;
	protected $listViewCount;

	public function getPageTitle(\App\Http\Vtiger_Request $request): string
	{
		return 'LBL_DELAYED_EMAILS';
	}

	public function preProcess(\App\Http\Vtiger_Request $request, $display = true)
	{
		parent::preProcess($request, false);
		$this->prepareListViewData($request);
	}

	public function preProcessAjax(\App\Http\Vtiger_Request $request)
	{
		$this->prepareListViewData($request);
	}

	protected function createListViewModel(\App\Http\Vtiger_Request $request)
	{
		return \App\Modules\DelayedEmails\Models\ListView::getInstance($request->getModule());
	}

	protected function prepareListViewData(\App\Http\Vtiger_Request $request): void
	{
		$viewer = $this->getViewer($request);
		$pageNumber = $request->get('page');
		$orderBy = $request->get('orderby');
		$sortOrder = $request->get('sortorder');
		$searchParams = $request->get('searchParams');
		$searchKey = $request->get('search_key');
		$searchValue = $request->get('search_value');

		if (empty($pageNumber)) {
			$pageNumber = 1;
		}
		if (empty($orderBy)) {
			$orderBy = 'send_after';
			$sortOrder = 'ASC';
		}

		if (!$this->listViewModel) {
			$this->listViewModel = $this->createListViewModel($request);
		}
		$listViewModel = $this->listViewModel;

		$availableColumns = array_keys($listViewModel->getModule()->getListFields());
		if (!empty($orderBy) && !in_array($orderBy, $availableColumns, true)) {
			$orderBy = $availableColumns ? reset($availableColumns) : null;
			$sortOrder = 'ASC';
		}

		$sortOrder = strtoupper((string) $sortOrder) === 'DESC' ? 'DESC' : 'ASC';
		$nextSortOrder = $sortOrder === 'ASC' ? 'DESC' : 'ASC';
		$sortImage = $sortOrder === 'ASC'
			? 'glyphicon glyphicon-chevron-down'
			: 'glyphicon glyphicon-chevron-up';

		$pagingModel = new \App\Modules\Base\Models\Paging();
		$pagingModel->set('page', $pageNumber);

		if (!empty($searchKey) && !empty($searchValue)) {
			$listViewModel->set('search_key', $searchKey);
			$listViewModel->set('search_value', $searchValue);
		}
		$viewer->assign('SEARCH_PARAMS', !empty($searchParams) ? $searchParams : []);
		if (!empty($searchParams)) {
			$listViewModel->set('searchParams', $searchParams);
		}

		if (!empty($orderBy)) {
			$listViewModel->set('orderby', $orderBy);
			$listViewModel->set('sortorder', $sortOrder);
		}

		if (!$this->listViewHeaders) {
			$this->listViewHeaders = $listViewModel->getListViewHeaders();
		}
		if (!$this->listViewEntries) {
			$this->listViewEntries = $listViewModel->getListViewEntries($pagingModel);
		}
		$noOfEntries = count($this->listViewEntries);
		if (!isset($this->listViewLinks)) {
			$this->listViewLinks = $listViewModel->getListViewLinks();
		}

		$viewer->assign('LISTVIEW_LINKS', $this->listViewLinks);
		$viewer->assign('MODULE_MODEL', $listViewModel->getModule());
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
		if (!isset($this->listViewCount)) {
			$this->listViewCount = $listViewModel->getListViewCount();
		}
		$totalCount = $this->listViewCount;
		$pagingModel->set('totalCount', (int) $totalCount);
		$viewer->assign('PAGE_COUNT', $pagingModel->getPageCount());
		$viewer->assign('LISTVIEW_COUNT', $totalCount);
		$viewer->assign('START_PAGIN_FROM', $pagingModel->getStartPagingFrom());
		$viewer->assign('LIST_MAX_ENTRIES_MASS_EDIT', \App\Core\AppConfig::main('listMaxEntriesMassEdit'));
		$viewer->assign('AUTO_REFRESH_LIST_ON_CHANGE', \App\Core\AppConfig::performance('AUTO_REFRESH_RECORD_LIST_ON_SELECT_CHANGE'));
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$viewer = $this->getViewer($request);
		if ($request->isAjax()) {
			$viewer->view('ListViewContent.tpl', $request->getModule());
		} else {
			$viewer->assign('VIEW', $request->get('view'));
			$viewer->view('ListView.tpl', $request->getModule());
		}
	}

	public function getFooterScripts(\App\Http\Vtiger_Request $request)
	{
		$headerScriptInstances = parent::getFooterScripts($request);
		$moduleName = $request->getModule();
		$jsFileNames = [
			'modules.Base.resources.Vtiger',
			'modules.Base.resources.ListView',
			'modules.Base.resources.ListSearch',
			"modules.$moduleName.resources.ListSearch",
		];
		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		return array_merge($headerScriptInstances, $jsScriptInstances);
	}
}
