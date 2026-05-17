<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * @project FreeCRM
 * @author bmankowski@gmail.com
 * @copyright (c) FreeCRM
 * @license FreeCRM Public License 1.0
 */

declare(strict_types=1);

namespace App\Modules\DocumentTemplates\Views;

/**
 * List view for document templates (configuration table).
 */
class ListView extends \App\Modules\Base\Views\Index
{
	protected $listViewEntries;
	protected $listViewHeaders;
	/** @var \App\Modules\DocumentTemplates\Models\ListView|null */
	protected $listViewModel;
	protected $listViewLinks;
	protected $listViewCount;

	public function checkPermission(\App\Http\Vtiger_Request $request): void
	{
		\App\Modules\DocumentTemplates\Models\Module::checkRequestPermission($request);
	}

	public function preProcess(\App\Http\Vtiger_Request $request, $display = true): void
	{
		parent::preProcess($request, false);
		$this->prepareListViewData($request);
	}

	public function preProcessAjax(\App\Http\Vtiger_Request $request): void
	{
		$this->prepareListViewData($request);
	}

	protected function createListViewModel(\App\Http\Vtiger_Request $request)
	{
		return \App\Modules\DocumentTemplates\Models\ListView::getInstance($request->getModule());
	}

	protected function prepareListViewHeaderData($viewer, $qualifiedModuleName): void
	{
		$moduleModel = \App\Modules\DocumentTemplates\Models\Module::getInstance('DocumentTemplates');
		$viewer->assign('SUPPORTED_MODULE_MODELS', \App\Modules\DocumentTemplates\Models\Module::getSupportedModules());
		$viewer->assign('CREATE_RECORD_URL', $moduleModel->getCreateRecordUrl());
		$viewer->assign('IMPORT_VIEW_URL', 'index.php?module=DocumentTemplates&view=Import');
	}

	protected function prepareListViewData(\App\Http\Vtiger_Request $request): void
	{
		$viewer = $this->getViewer($request);
		$qualifiedModuleName = $request->getModule();
		$pageNumber = $request->get('page');
		$orderBy = $request->get('orderby');
		$sortOrder = $request->get('sortorder');
		$sourceModule = $request->get('sourceModule');
		$forModule = $request->get('formodule');
		$searchParams = $request->get('searchParams');
		$searchKey = $request->get('search_key');
		$searchValue = $request->get('search_value');

		if (empty($pageNumber)) {
			$pageNumber = 1;
		}

		if (!$this->listViewModel) {
			$this->listViewModel = $this->createListViewModel($request);
		}
		$listViewModel = $this->listViewModel;

		$availableColumns = array_keys($listViewModel->getModule()->getListFields());

		if (!empty($orderBy) && !in_array($orderBy, $availableColumns, true)) {
			$orderBy = $availableColumns ? (string) reset($availableColumns) : null;
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
		if (!empty($searchParams)) {
			$listViewModel->set('searchParams', $searchParams);
			$viewer->assign('SEARCH_PARAMS', $searchParams);
		} else {
			$viewer->assign('SEARCH_PARAMS', []);
		}

		if (!empty($orderBy)) {
			$listViewModel->set('orderby', $orderBy);
			$listViewModel->set('sortorder', $sortOrder);
		}
		if (!empty($sourceModule)) {
			$listViewModel->set('sourceModule', $sourceModule);
		}
		if (!empty($forModule)) {
			$listViewModel->set('formodule', $forModule);
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
		$pagingModel->set('totalCount', (int) $this->listViewCount);
		$viewer->assign('PAGE_COUNT', $pagingModel->getPageCount());
		$viewer->assign('LISTVIEW_COUNT', $this->listViewCount);
		$viewer->assign('START_PAGIN_FROM', $pagingModel->getStartPagingFrom());
		$viewer->assign('SOURCE_MODULE', $sourceModule);
		$this->prepareListViewHeaderData($viewer, $qualifiedModuleName);
		$viewer->assign('LIST_MAX_ENTRIES_MASS_EDIT', \App\Core\AppConfig::main('listMaxEntriesMassEdit'));
		$viewer->assign(
			'AUTO_REFRESH_LIST_ON_CHANGE',
			\App\Core\AppConfig::performance('AUTO_REFRESH_RECORD_LIST_ON_SELECT_CHANGE')
		);
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		if ($request->isAjax()) {
			$viewer->view('ListViewContent.tpl', $moduleName);
			return;
		}
		$viewer->assign('VIEW', $request->get('view'));
		$viewer->view('ListView.tpl', $moduleName);
	}

	public function getFooterScripts(\App\Http\Vtiger_Request $request)
	{
		$headerScriptInstances = parent::getFooterScripts($request);
		$moduleName = $request->getModule();
		$jsFileNames = [
			'modules.Base.resources.ListView',
			"modules.$moduleName.resources.ListView",
			'modules.Base.resources.ListSearch',
			"modules.$moduleName.resources.ListSearch",
		];
		return array_merge($headerScriptInstances, $this->checkAndConvertJsScripts($jsFileNames));
	}
}
