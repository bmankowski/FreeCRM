<?php

namespace App\Modules\Base\Views;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * *********************************************************************************** */

class Detail extends \App\Modules\Base\Views\Index
{

	protected $record = null;
	protected $recordStructure = null;
	public $defaultMode = false;

	public function __construct()
	{
		parent::__construct();
		$this->exposeMethod('showDetailViewByMode');
		$this->exposeMethod('showModuleDetailView');
		$this->exposeMethod('showModuleSummaryView');
		$this->exposeMethod('showModuleBasicView');
		$this->exposeMethod('showRecentActivities');
		$this->exposeMethod('showRecentComments');
		$this->exposeMethod('showRelatedList');
		$this->exposeMethod('showChildComments');
		$this->exposeMethod('showAllComments');
		$this->exposeMethod('showThreadComments');
		$this->exposeMethod('getActivities');
		$this->exposeMethod('showRelatedProductsServices');
		$this->exposeMethod('showRelatedRecords');
		$this->exposeMethod('showRelatedTree');
		$this->exposeMethod('showRecentRelation');
		$this->exposeMethod('showOpenStreetMap');
	}

	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$recordId = $request->get('record');
		if (!is_numeric($recordId)) {
			throw new \App\Exceptions\NoPermittedToRecord('LBL_NO_PERMISSIONS_FOR_THE_RECORD');
		}
		$recordPermission = \App\Modules\Users\Models\Privileges::isPermitted($moduleName, 'DetailView', $recordId);
		if (!$recordPermission) {
			throw new \App\Exceptions\NoPermittedToRecord('LBL_NO_PERMISSIONS_FOR_THE_RECORD');
		}
		return true;
	}

	public function getBreadcrumbTitle(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		return \App\Runtime\Vtiger_Language_Handler::translate('LBL_VIEW_DETAIL', $moduleName);
	}

	public function preProcess(\App\Http\Vtiger_Request $request, $display = true)
	{
		parent::preProcess($request, false);
		$this->assignDetailViewData($request);
	}

	protected function assignDetailViewData(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$recordId = $request->get('record');
		if (!$this->record) {
			$this->record = \App\Modules\Base\Models\DetailView::getInstance($moduleName, $recordId);
		}
		$recordModel = $this->record->getRecord();
		$this->recordStructure = \App\Modules\Base\Models\RecordStructure::getInstanceFromRecordModel($recordModel, \App\Modules\Base\Models\RecordStructure::RECORD_STRUCTURE_MODE_DETAIL);
		$summaryInfo = [];
		// Take first block information as summary information
		$stucturedValues = $this->recordStructure->getStructure();
		$fieldsInHeader = method_exists($this->recordStructure, 'getFieldInHeader') ? $this->recordStructure->getFieldInHeader() : false;
		foreach ($stucturedValues as $blockLabel => $fieldList) {
			$summaryInfo[$blockLabel] = $fieldList;
			break;
		}

		$eventHandler = new \App\Events\EventHandler();
		$eventHandler->setRecordModel($recordModel);
		$eventHandler->setModuleName($moduleName);
		$eventHandler->trigger('DetailViewBefore');

		$detailViewLinkParams = array('MODULE' => $moduleName, 'RECORD' => $recordId);

		$detailViewLinks = $this->record->getDetailViewLinks($detailViewLinkParams);
		$this->record->getWidgets($detailViewLinkParams);
		// Record-to-record navigation is handled in the frontend using the ListView context.
		// Keep server-side URLs optional (may be assigned by module-specific implementations).
		$navigationInfo = false; //ListViewSession::getListViewNavigation($recordId);

		$viewer = $this->getViewer($request);
		$viewer->assign('RECORD', $recordModel);
		$viewer->assign('NAVIGATION', $navigationInfo);
		// Show prev/next buttons; they will be disabled when no navigation context exists.
		$viewer->assign('NO_PAGINATION', false);
		// Always initialize URLs to avoid undefined template vars.
		$viewer->assign('PREVIOUS_RECORD_URL', '');
		$viewer->assign('NEXT_RECORD_URL', '');
		$viewer->assign('COLORLISTHANDLERS', \App\Modules\Settings\DataAccess\Models\Module::executeColorListHandlers($moduleName, $recordId, $recordModel));

		//Intially make the prev and next records as null
		$prevRecordId = null;
		$nextRecordId = null;
		$found = false;
		if ($navigationInfo) {
			foreach ($navigationInfo as $page => $pageInfo) {
				foreach ($pageInfo as $index => $record) {
					//If record found then next record in the interation
					//will be next record
					if ($found) {
						$nextRecordId = $record;
						break;
					}
					if ($record == $recordId) {
						$found = true;
					}
					//If record not found then we are assiging previousRecordId
					//assuming next record will get matched
					if (!$found) {
						$prevRecordId = $record;
					}
				}
				//if record is found and next record is not calculated we need to perform iteration
				if ($found && !empty($nextRecordId)) {
					break;
				}
			}
		}

		// Server-side fallback: compute prev/next within current ListView filter (viewname) stored in session.
		// This makes navigation work even when JS sessionStorage context is missing.
		if (empty($prevRecordId) && empty($nextRecordId)) {
			try {
				$viewId = \App\View\CustomView::getCurrentView($moduleName);
				if ($viewId) {
					$orderBy = \App\View\CustomView::getSortby($moduleName);
					$sortOrder = \App\View\CustomView::getSorder($moduleName) ?: 'ASC';
					$page = (int) \App\View\CustomView::getCurrentPage($moduleName, $viewId);
					if ($page < 1) {
						$page = 1;
					}

					$listViewModel = \App\Modules\Base\Models\ListView::getInstance($moduleName, (int) $viewId);
					if (!empty($orderBy)) {
						$listViewModel->set('orderby', $orderBy);
						$listViewModel->set('sortorder', $sortOrder);
					}

					$findOnPage = function (int $pageNumber) use ($listViewModel, $recordId): array {
						$pagingModel = new \App\Modules\Base\Models\Paging();
						$pagingModel->set('page', $pageNumber);
						$entries = $listViewModel->getListViewEntries($pagingModel);
						$ids = array_keys($entries);
						$idx = array_search((string) $recordId, array_map('strval', $ids), true);
						return [$ids, $idx, $pagingModel->isNextPageExists()];
					};

					[$ids, $idx, $hasNextPage] = $findOnPage($page);
					$resolvedPage = $page;
					if ($idx === false) {
						// Best-effort: try a few likely pages if the record wasn't on the last ListView page.
						$candidates = array_values(array_unique(array_filter([(int) $page, 1, (int) $page - 1, (int) $page + 1], fn($p) => $p >= 1)));
						foreach ($candidates as $candidatePage) {
							[$ids, $idx, $hasNextPage] = $findOnPage((int) $candidatePage);
							if ($idx !== false) {
								$resolvedPage = (int) $candidatePage;
								break;
							}
						}
					}

					if ($idx !== false && !empty($ids)) {
						$idx = (int) $idx;
						if ($idx > 0) {
							$prevRecordId = $ids[$idx - 1] ?? null;
						} elseif ($resolvedPage > 1) {
							// previous record from previous page
							[$prevIds] = $findOnPage($resolvedPage - 1);
							if (!empty($prevIds)) {
								$prevRecordId = end($prevIds) ?: null;
							}
						}
						if ($idx < count($ids) - 1) {
							$nextRecordId = $ids[$idx + 1] ?? null;
						} elseif ($hasNextPage) {
							// next record from next page
							[$nextIds] = $findOnPage($resolvedPage + 1);
							if (!empty($nextIds)) {
								$nextRecordId = $nextIds[0] ?? null;
							}
						}
					}
				}
			} catch (\Throwable $e) {
				// Best-effort; ignore failures.
			}
		}

		$moduleModel = $this->record->getModule();
		if (!empty($prevRecordId)) {
			$viewer->assign('PREVIOUS_RECORD_URL', $moduleModel->getDetailViewUrl($prevRecordId));
		}
		if (!empty($nextRecordId)) {
			$viewer->assign('NEXT_RECORD_URL', $moduleModel->getDetailViewUrl($nextRecordId));
		}
		$currentUserModel = $request->getUser();
		$selectedTabLabel = $request->get('tab_label');
		$requestMode = $request->get('requestMode');
		$mode = $request->getMode();
		if (empty($selectedTabLabel) && !empty($requestMode)) {
			if ($requestMode == 'full') {
				$selectedTabLabel = 'LBL_RECORD_DETAILS';
			} else {
				$selectedTabLabel = 'LBL_RECORD_SUMMARY';
			}
		} elseif (empty($requestMode) && empty($mode)) {
			$selectedTabLabel = \App\Core\AppConfig::module($moduleName, 'DEFAULT_VIEW_RECORD');
			if (empty($selectedTabLabel)) {
				if ($currentUserModel->get('default_record_view') === 'Detail') {
					$selectedTabLabel = 'LBL_RECORD_DETAILS';
				} else {
					if ($moduleModel->isSummaryViewSupported() && $this->record->widgetsList) {
						$selectedTabLabel = 'LBL_RECORD_SUMMARY';
					} else {
						$selectedTabLabel = 'LBL_RECORD_DETAILS';
					}
				}
			}
		}
		if (isset($detailViewLinks['DETAILVIEWTAB']) && is_array($detailViewLinks['DETAILVIEWTAB'])) {
			foreach ($detailViewLinks['DETAILVIEWTAB'] as $link) {
				if ($link->getLabel() == $selectedTabLabel) {
					$params = \vtlib\Functions:: getQueryParams($link->getUrl());
					$this->defaultMode = $params['mode'];
					break;
				}
			}
		}

		// Process header widgets
		$processedHeaderWidgets = [];
		if (!empty($detailViewLinks['DETAIL_VIEW_HEADER_WIDGET'])) {
			$widgetModel = new \App\Modules\Base\Models\Widget();
			foreach ($detailViewLinks['DETAIL_VIEW_HEADER_WIDGET'] as $widget) {
				$processedHeaderWidgets[] = $widgetModel->processWidget($widget, $recordModel);
			}
		}
		
		$viewer->assign('SELECTED_TAB_LABEL', $selectedTabLabel);
		$viewer->assign('MODULE_MODEL', $moduleModel);
		$viewer->assign('MODULE_TYPE', $moduleModel->getModuleType());
		$viewer->assign('DETAILVIEW_LINKS', $detailViewLinks);
		$viewer->assign('PROCESSED_HEADER_WIDGETS', $processedHeaderWidgets);
		$viewer->assign('DETAILVIEW_WIDGETS', $this->record->widgets);
		$viewer->assign('FIELDS_HEADER', $fieldsInHeader);
		$viewer->assign('CUSTOM_FIELDS_HEADER', $this->record->getCustomHeaderFields());
		$viewer->assign('IS_EDITABLE', $this->record->getRecord()->isEditable($moduleName));
		$viewer->assign('IS_DELETABLE', $this->record->getRecord()->isDeletable($moduleName));

		$linkParams = array('MODULE' => $moduleName, 'ACTION' => $request->get('view'));
		$linkModels = $this->record->getSideBarLinks($linkParams);

		// Process sidebar links to determine active link
		$activeLinkLabel = $this->processSidebarLinks($linkModels, $request);

		$viewer->assign('QUICK_LINKS', $linkModels);
		$viewer->assign('ACTIVE_SIDEBAR_LINK', $activeLinkLabel);
		$viewer->assign('DEFAULT_RECORD_VIEW', $currentUserModel->get('default_record_view'));

		$picklistDependencyDatasource = \App\Modules\PickList\DependencyPicklist::getPicklistDependencyDatasource($moduleName);
		$viewer->assign('PICKLIST_DEPENDENCY_DATASOURCE', \App\Utils\Json::encode($picklistDependencyDatasource));
		
		// Prepare inventory data if module supports inventory
		if ($moduleModel->isInventory()) {
			$this->prepareDetailInventoryData($viewer, $moduleName, $recordModel);
		}
		// MainLayout handles rendering, no separate preProcess template needed
	}
	
	/**
	 * Prepare data for DetailViewInventoryView template
	 * Moves function calls from templates to controller for better MVC separation
	 */
	protected function prepareDetailInventoryData($viewer, $moduleName, $recordModel)
	{
		$inventoryField = \App\Modules\Base\Models\InventoryField::getInstance($moduleName);
		$fields = $inventoryField->getFields(true, [], 'Detail');
		
		// Always assign FIELDS, even if empty, so template can check it
		if ($fields === null || !is_array($fields) || count($fields) == 0) {
			$viewer->assign('FIELDS', []);
			return;
		}
		
		$viewer->assign('INVENTORY_FIELD', $inventoryField);
		$viewer->assign('FIELDS', $fields);
		
		$columns = $inventoryField->getColumns();
		$inventoryRows = $recordModel->getInventoryData();
		$mainParams = $inventoryField->getMainParams($fields[1]);
		
		$viewer->assign('COLUMNS', $columns);
		$viewer->assign('INVENTORY_ROWS', $inventoryRows);
		$viewer->assign('MAIN_PARAMS', $mainParams);
		$viewer->assign('COUNT_FIELDS0', count($fields[0]));
		$viewer->assign('COUNT_FIELDS1', count($fields[1]));
		$viewer->assign('COUNT_FIELDS2', count($fields[2]));
		
		$baseCurrency = \App\Modules\Base\Helpers\Util::getBaseCurrency();
		$viewer->assign('BASE_CURRENCY', $baseCurrency);
		
		// Prepare currency symbol and rate if currency column exists
		if (in_array("currency", $columns)) {
			if (count($inventoryRows) > 0 && !empty($inventoryRows[0]['currency'])) {
				$currency = $inventoryRows[0]['currency'];
			} else {
				$currency = $baseCurrency['id'];
			}
			$viewer->assign('CURRENCY', $currency);
			$viewer->assign('CURRENCY_SYMBOLAND', \vtlib\Functions::getCurrencySymbolandRate($currency));
		}
		
		// Pre-calculate record types for inventory rows (used in template loop)
		$rowModules = [];
		foreach ($inventoryRows as $key => $inventoryRow) {
			if (!empty($inventoryRow['name'])) {
				$rowModules[$key] = \App\Records\Record::getType($inventoryRow['name']);
			}
		}
		$viewer->assign('INVENTORY_ROW_MODULES', $rowModules);
		
		// Pre-calculate summary values for footer
		$summaryValues = [];
		foreach ($fields[1] as $field) {
			if ($field->isSummary()) {
				$sum = $field->getSummaryValuesFromData($inventoryRows);
				$summaryValues[$field->getName()] = \App\Fields\CurrencyField::convertToUserFormat($sum, null, true);
			}
		}
		$viewer->assign('INVENTORY_SUMMARY_VALUES', $summaryValues);
		
		// Pre-assign fields text align right array (used for styling)
		$viewer->assign('FIELDS_TEXT_ALIGN_RIGHT', ['TotalPrice','Tax','MarginP','Margin','Purchase','Discount','NetPrice','GrossPrice','UnitPrice','Quantity']);
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$mode = $request->getMode();
		if ($request->isAjax()) {
			// AJAX mode requests - return partial content
			echo $this->invokeExposedMethod($mode, $request);
			return;
		}
		
		// Initial page load - render full DetailView with MainLayout
		// Data already assigned in preProcess via assignDetailViewData()
		$recordId = $request->get('record');
		$moduleName = $request->getModule();
		if (!$this->record) {
			$this->record = \App\Modules\Base\Models\DetailView::getInstance($moduleName, $recordId);
		}
		
		// Check if URL contains mode parameter for direct access to specific tab/view
		if (!empty($mode) && method_exists($this, $mode)) {
			// Direct URL access to specific mode (e.g., showRelatedList)
			$detailContent = $this->$mode($request);
		} else {
			// Determine default mode and generate content
			$defaultMode = $this->defaultMode;
			if ($defaultMode == 'showDetailViewByMode') {
				$currentUserModel = $request->getUser();
				$this->record->getWidgets(['MODULE' => $moduleName, 'RECORD' => $recordId]);
				if (!($currentUserModel->get('default_record_view') === 'Summary' && $this->record->widgetsList)) {
					$defaultMode = 'showModuleDetailView';
				}
			} else if ($defaultMode === false) {
				$defaultMode = 'showDetailViewByMode';
			}
			
			// Generate the detail content
			$detailContent = $this->$defaultMode($request);
		}
		
		// RECORD already assigned in preProcess, only assign DETAIL_CONTENT
		$viewer = $this->getViewer($request);
		$viewer->assign('DETAIL_CONTENT', $detailContent);
		$viewer->view('DetailView.tpl', $moduleName);
	}

	public function getHeaderCss(\App\Http\Vtiger_Request $request)
	{
		$parentCssInstances = parent::getHeaderCss($request);
		$cssFileNames = [
			'~libraries/leaflet/leaflet.css',
			'~libraries/leaflet/plugins/markercluster/MarkerCluster.Default.css',
			'~libraries/leaflet/plugins/markercluster/MarkerCluster.css',
			'~libraries/leaflet/plugins/awesome-markers/leaflet.awesome-markers.css',
		];
		$modalInstances = $this->checkAndConvertCssStyles($cssFileNames);
		$cssInstances = array_merge($parentCssInstances, $modalInstances);
		return $cssInstances;
	}

	public function getFooterScripts(\App\Http\Vtiger_Request $request)
	{
		$headerScriptInstances = parent::getFooterScripts($request);
		$moduleName = $request->getModule();

		$jsFileNames = array(
			'modules.Base.resources.Detail',
			'modules.Base.resources.GenerateModal',
			"modules.$moduleName.resources.Detail",
			'modules.Base.resources.RelatedList',
			"modules.$moduleName.resources.RelatedList",
			'modules.Base.resources.Widgets',
			'modules.Base.resources.ListSearch',
			"modules.$moduleName.resources.ListSearch",
			'~libraries/leaflet/leaflet.js',
			'~libraries/leaflet/plugins/markercluster/leaflet.markercluster.js',
			'~libraries/leaflet/plugins/awesome-markers/leaflet.awesome-markers.js',
			"modules.OpenStreetMap.resources.Map",
		);
		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		$headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);
		return $headerScriptInstances;
	}

	public function showDetailViewByMode($request)
	{
		$requestMode = $request->get('requestMode');
		if ($requestMode == 'full') {
			return $this->showModuleDetailView($request);
		}
		return $this->showModuleBasicView($request);
	}

	/**
	 * Function shows the entire detail for the record
	 * @param \App\Http\Vtiger_Request $request
	 * @return <type>
	 */
	public function showModuleDetailView(\App\Http\Vtiger_Request $request)
	{
		$recordId = $request->get('record');
		$moduleName = $request->getModule();

		if (!$this->record) {
			$this->record = \App\Modules\Base\Models\DetailView::getInstance($moduleName, $recordId);
		}
		$recordModel = $this->record->getRecord();
		if (!$this->recordStructure) {
			$this->recordStructure = \App\Modules\Base\Models\RecordStructure::getInstanceFromRecordModel($recordModel, \App\Modules\Base\Models\RecordStructure::RECORD_STRUCTURE_MODE_DETAIL);
		}
		$structuredValues = $this->recordStructure->getStructure();

		$moduleModel = $recordModel->getModule();

		// CRITICAL: Use the SAME viewer instance from preProcess
		$viewer = $this->viewer ? $this->viewer : $this->getViewer($request);
		$viewer->assign('VIEW', $request->get('view'));
		$viewer->assign('RECORD', $recordModel);
		$viewer->assign('RECORD_STRUCTURE', $structuredValues);
		$viewer->assign('BLOCK_LIST', $moduleModel->getBlocks());
		$viewer->assign('USER_MODEL', $request->getUser());
		$viewer->assign('MODULE_NAME', $moduleName);
		$viewer->assign('IS_AJAX_ENABLED', $this->isAjaxEnabled($recordModel));
		$viewer->assign('MODULE_TYPE', $moduleModel->getModuleType());
		return $viewer->view('DetailViewFullContents.tpl', $moduleName, true);
	}

	public function showModuleSummaryView(\App\Http\Vtiger_Request $request)
	{
		$recordId = $request->get('record');
		$moduleName = $request->getModule();

		if (!$this->record) {
			$this->record = \App\Modules\Base\Models\DetailView::getInstance($moduleName, $recordId);
		}
		$recordModel = $this->record->getRecord();
		$recordStrucure = \App\Modules\Base\Models\RecordStructure::getInstanceFromRecordModel($recordModel, \App\Modules\Base\Models\RecordStructure::RECORD_STRUCTURE_MODE_SUMMARY);

		$moduleModel = $recordModel->getModule();
		// CRITICAL: Use the SAME viewer instance that was used in preProcess, don't create a new one
		$viewer = $this->viewer ? $this->viewer : $this->getViewer($request);
		$viewer->assign('RECORD', $recordModel);
		$viewer->assign('BLOCK_LIST', $moduleModel->getBlocks());
		$viewer->assign('USER_MODEL', $request->getUser());
		$viewer->assign('VIEW', $request->get('view'));
		$viewer->assign('MODULE_NAME', $moduleName);
		$viewer->assign('IS_AJAX_ENABLED', $this->isAjaxEnabled($recordModel));
		$viewer->assign('SUMMARY_RECORD_STRUCTURE', $recordStrucure->getStructure());
		if (is_callable($moduleName . "_Record_Model", 'getStructure')) {
			$viewer->assign('SUMMARY_RECORD_STRUCTURE', $recordStrucure->getStructure());
		}
		$viewer->assign('IS_READ_ONLY', $request->getBoolean('isReadOnly'));
		return $viewer->view('ModuleSummaryBlockView.tpl', $moduleName, true);
	}

	/**
	 * Function shows basic detail for the record
	 * @param <type> $request
	 */
	public function showModuleBasicView(\App\Http\Vtiger_Request $request)
	{
		$recordId = $request->get('record');
		$moduleName = $request->getModule();

		if (!$this->record) {
			$this->record = \App\Modules\Base\Models\DetailView::getInstance($moduleName, $recordId);
		}
		$recordModel = $this->record->getRecord();
		$detailViewLinkParams = array('MODULE' => $moduleName, 'RECORD' => $recordId);
		$detailViewLinks = $this->record->getDetailViewLinks($detailViewLinkParams);
		$this->record->getWidgets($detailViewLinkParams);
		// CRITICAL: Use the SAME viewer instance from preProcess
		$viewer = $this->viewer ? $this->viewer : $this->getViewer($request);
		$viewer->assign('RECORD', $recordModel);
		$viewer->assign('MODULE_SUMMARY', $this->showModuleSummaryView($request));
		$viewer->assign('DETAILVIEW_WIDGETS', $this->record->widgets);
		$viewer->assign('DETAILVIEW_LINKS', $detailViewLinks);
		$viewer->assign('USER_MODEL', $request->getUser());
		$viewer->assign('IS_AJAX_ENABLED', $this->isAjaxEnabled($recordModel));
		$viewer->assign('MODULE_NAME', $moduleName);
		$viewer->assign('VIEW', $request->get('view'));

		if (!$this->recordStructure) {
			$this->recordStructure = \App\Modules\Base\Models\RecordStructure::getInstanceFromRecordModel($recordModel, \App\Modules\Base\Models\RecordStructure::RECORD_STRUCTURE_MODE_DETAIL);
		}
		$structuredValues = $this->recordStructure->getStructure();

		$moduleModel = $recordModel->getModule();

		$viewer->assign('RECORD_STRUCTURE', $structuredValues);
		$viewer->assign('MODULE_MODEL', $moduleModel);
		$viewer->assign('BLOCK_LIST', $moduleModel->getBlocks());
		if ($moduleModel->isSummaryViewSupported() && $this->record->widgetsList) {
			return $viewer->view('SummaryViewWidgets.tpl', $moduleName, true);
		} else {
			return $viewer->view('DetailViewSummaryContents.tpl', $moduleName, true);
		}
	}

	/**
	 * Function returns recent changes made on the record
	 * @param \App\Http\Vtiger_Request $request
	 */
	public function showRecentActivities(\App\Http\Vtiger_Request $request)
	{
		$type = 'changes';
		$parentRecordId = $request->get('record');
		$pageNumber = $request->get('page');
		$limit = $request->get('limit');
		$whereCondition = $request->get('whereCondition');
		$moduleName = $request->getModule();

		if (empty($pageNumber)) {
			$pageNumber = 1;
		}

		$pagingModel = new \App\Modules\Base\Models\Paging();
		$pagingModel->set('page', $pageNumber);
		if (!empty($limit)) {
			$pagingModel->set('limit', $limit);
		} else {
			$limit = \App\Core\AppConfig::module('ModTracker', 'NUMBER_RECORDS_ON_PAGE');
			$pagingModel->set('limit', $limit);
		}
		if (!empty($whereCondition)) {
			$type = is_array($whereCondition) ? current($whereCondition) : $whereCondition;
		}
		$recentActivities = \App\Modules\ModTracker\Models\Record::getUpdates($parentRecordId, $pagingModel, $type);
		$pagingModel->calculatePageRange(count($recentActivities));

		if ($pagingModel->getCurrentPage() == ceil(\App\Modules\ModTracker\Models\Record::getTotalRecordCount($parentRecordId, $type) / $pagingModel->getPageLimit())) {
			$pagingModel->set('nextPageExists', false);
		} else {
			$pagingModel->set('nextPageExists', true);
		}
		$currentUser = $request->getUser();
		if ($type == 'changes') {
			$newChange = $request->has('newChange') ? $request->get('newChange') : \App\Modules\ModTracker\Models\Record::isNewChange($parentRecordId, $currentUser->getRealId());
		}
		$viewer = $this->getViewer($request);
		$viewer->assign('TYPE', $type);
		$viewer->assign('NEW_CHANGE', $newChange);
		$viewer->assign('PARENT_RACORD_ID', $parentRecordId);
		$viewer->assign('RECENT_ACTIVITIES', $recentActivities);
		$viewer->assign('MODULE_NAME', $moduleName);
		$viewer->assign('MODULE_MODEL', \App\Modules\Base\Models\Module::getInstance($moduleName));
		$viewer->assign('MODULE_BASE_NAME', 'ModTracker');
		$viewer->assign('PAGING_MODEL', $pagingModel);
		$viewer->assign('IS_READ_ONLY', $request->getBoolean('isReadOnly'));
		$defaultView = \App\Core\AppConfig::module('ModTracker', 'DEFAULT_VIEW');
		if ($defaultView == 'List') {
			$tplName = 'RecentActivities.tpl';
		} else {
			$tplName = 'RecentActivitiesTimeLine.tpl';
		}
		if (!$request->get('skipHeader')) {
			$viewer->view('RecentActivitiesHeader.tpl', $moduleName);
		}
		return $viewer->view($tplName, $moduleName, true);
	}

	/**
	 * Function returns latest comments
	 * @param \App\Http\Vtiger_Request $request
	 * @return <type>
	 */
	public function showRecentComments(\App\Http\Vtiger_Request $request)
	{
		$parentId = $request->get('record');
		$pageNumber = $request->get('page');
		$limit = $request->get('limit');
		$moduleName = $request->getModule();

		if (empty($pageNumber)) {
			$pageNumber = 1;
		}

		$pagingModel = new \App\Modules\Base\Models\Paging();
		$pagingModel->set('page', $pageNumber);
		if (!empty($limit)) {
			$pagingModel->set('limit', $limit);
		}

		$recentComments = \App\Modules\ModComments\Models\Record::getRecentComments($parentId, $pagingModel);
		$pagingModel->calculatePageRange(count($recentComments));
		$currentUserModel = $request->getUser();
		$modCommentsModel = \App\Modules\Base\Models\Module::getInstance('ModComments');

		$viewer = $this->getViewer($request);
		$viewer->assign('COMMENTS', $recentComments);
		$viewer->assign('CURRENTUSER', $currentUserModel);
		$viewer->assign('MODULE_NAME', $moduleName);
		$viewer->assign('PAGING_MODEL', $pagingModel);
		$viewer->assign('COMMENTS_MODULE_MODEL', $modCommentsModel);
		$viewer->assign('IS_READ_ONLY', $request->getBoolean('isReadOnly'));
		return $viewer->view('RecentComments.tpl', $moduleName, true);
	}

	/**
	 * Function returns related records
	 * @param \App\Http\Vtiger_Request $request
	 * @return <type>
	 */
	public function showRelatedList(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$relatedModuleName = $request->get('relatedModule');
		$targetControllerClass = null;

		// Added to support related list view from the related module, rather than the base module.
		if (!$targetControllerClass = \App\Core\Loader::getComponentClassName('View', 'In' . $moduleName . 'Relation', $relatedModuleName, false)) {
			// If any module wants to have same view for all the relation, then invoke this.
			if (!$targetControllerClass = \App\Core\Loader::getComponentClassName('View', 'InRelation', $relatedModuleName, false)) {
				// Default related list
				$targetControllerClass = \App\Core\Loader::getComponentClassName('View', 'RelatedList', $moduleName);
			}
		}
		if ($targetControllerClass) {
			$targetController = new $targetControllerClass();
			return $targetController->process($request);
		}
	}

	/**
	 * Function sends the child comments for a comment
	 * @param \App\Http\Vtiger_Request $request
	 * @return mixed
	 */
	public function showChildComments(\App\Http\Vtiger_Request $request)
	{
		$parentCommentId = $request->get('commentid');
		$parentCommentModel = \App\Modules\Base\Models\Record::getInstanceById($parentCommentId);
		$childComments = $parentCommentModel->getChildComments();
		$currentUserModel = $request->getUser();
		$modCommentsModel = \App\Modules\Base\Models\Module::getInstance('ModComments');
		$moduleName = $request->getModule();

		$viewer = $this->getViewer($request);
		$viewer->assign('PARENT_COMMENTS', $childComments);
		$viewer->assign('CURRENTUSER', $currentUserModel);
		$viewer->assign('COMMENTS_MODULE_MODEL', $modCommentsModel);
		$viewer->assign('TYPE_VIEW', "List");
		$viewer->assign('IS_READ_ONLY', $request->getBoolean('isReadOnly'));
		$viewer->assign('CURRENT_COMMENT', null);
		$viewer->assign('MODULE_NAME', $moduleName);
		return $viewer->view('CommentsList.tpl', $moduleName, true);
	}

	/**
	 * Function send all the comments in thead
	 * @param \App\Http\Vtiger_Request $request
	 * @return mixed
	 */
	public function showThreadComments(\App\Http\Vtiger_Request $request)
	{
		$parentRecordId = $request->get('record');
		$commentRecordId = $request->get('commentid');
		$moduleName = $request->getModule();
		$currentUserModel = $request->getUser();
		$parentCommentModels = \App\Modules\ModComments\Models\Record::getAllParentComments($parentRecordId);
		$currentCommentModel = \App\Modules\Base\Models\Record::getInstanceById($commentRecordId);

		$viewer = $this->getViewer($request);
		$viewer->assign('CURRENTUSER', $currentUserModel);
		$viewer->assign('PARENT_COMMENTS', $parentCommentModels);
		$viewer->assign('CURRENT_COMMENT', $currentCommentModel);
		$viewer->assign('IS_READ_ONLY', $request->getBoolean('isReadOnly'));
		return $viewer->view('ShowThreadComments.tpl', $moduleName, true);
	}

	/**
	 * Function sends all the comments for a parent(Accounts, Contacts etc)
	 * @param \App\Http\Vtiger_Request $request
	 * @return mixed
	 */
	public function showAllComments(\App\Http\Vtiger_Request $request)
	{
		$parentRecordId = $request->get('record');
		$commentRecordId = $request->get('commentid');
		$hierarchy = [];
		if ($request->has('hierarchy')) {
			$hierarchy = explode(',', $request->get('hierarchy'));
		}
		$moduleName = $request->getModule();
		$currentUserModel = $request->getUser();
		$modCommentsModel = \App\Modules\Base\Models\Module::getInstance('ModComments');
		$parentCommentModels = \App\Modules\ModComments\Models\Record::getAllParentComments($parentRecordId, $hierarchy);
		$currentCommentModel = [];
		if (!empty($commentRecordId)) {
			$currentCommentModel = \App\Modules\Base\Models\Record::getInstanceById($commentRecordId);
		}

		$hierarchyList = ['LBL_COMMENTS_0', 'LBL_COMMENTS_1', 'LBL_COMMENTS_2'];
		$level = \App\Core\ModuleHierarchy::getModuleLevel($request->getModule());
		if ($level > 0) {
			unset($hierarchyList[1]);
			if ($level > 1) {
				unset($hierarchyList[2]);
			}
		}

		$viewer = $this->getViewer($request);
		$viewer->assign('CURRENTUSER', $currentUserModel);
		$viewer->assign('PARENT_RECORD', $parentRecordId);
		$viewer->assign('HIERARCHY', $hierarchy);
		$viewer->assign('HIERARCHY_LIST', $hierarchyList);
		$viewer->assign('COMMENTS_MODULE_MODEL', $modCommentsModel);
		$viewer->assign('PARENT_COMMENTS', $parentCommentModels);
		$viewer->assign('CURRENT_COMMENT', $currentCommentModel);
		$viewer->assign('MODULE_NAME', $moduleName);
		$viewer->assign('IS_READ_ONLY', $request->getBoolean('isReadOnly'));
		return $viewer->view('ShowAllComments.tpl', $moduleName, true);
	}

	/**
	 * Function to get Ajax is enabled or not
	 * @param \App\Modules\Base\Models\Record record model
	 * @return <boolean> true/false
	 */
	public function isAjaxEnabled($recordModel)
	{
		return $recordModel->isEditable();
	}

	/**
	 * Function to get activities
	 * @param \App\Http\Vtiger_Request $request
	 * @return <List of activity models>
	 */
	public function getActivities(\App\Http\Vtiger_Request $request)
	{
		$moduleName = 'Calendar';
		$currentUserPriviligesModel = \App\Modules\Users\Models\Privileges::getCurrentUserPrivilegesModel();
		if ($currentUserPriviligesModel->hasModulePermission($moduleName)) {
			$moduleName = $request->getModule();
			$recordId = $request->get('record');
			$pageNumber = $request->get('page');
			$pageLimit = $request->get('limit');
			$sortOrder = $request->get('sortorder');
			$orderBy = $request->get('orderby');
			$type = $request->get('type');
			if (empty($pageNumber)) {
				$pageNumber = 1;
			}
			$pagingModel = new \App\Modules\Base\Models\Paging();
			$pagingModel->set('page', $pageNumber);
			$pagingModel->set('orderby', $orderBy);
			$pagingModel->set('sortorder', $sortOrder);
			if (!$request->isEmpty('totalCount')) {
				$pagingModel->set('totalCount', $request->get('totalCount'));
			}
			if (!empty($pageLimit)) {
				$pagingModel->set('limit', $pageLimit);
			} else {
				$pagingModel->set('limit', 10);
			}
			if (!$this->record) {
				$this->record = \App\Modules\Base\Models\DetailView::getInstance($moduleName, $recordId);
			}
			$recordModel = $this->record->getRecord();
			$moduleModel = $recordModel->getModule();

			$relatedActivities = $moduleModel->getCalendarActivities($type, $pagingModel, 'all', $recordId);

			$colorList = [];
			foreach ($relatedActivities as $activityModel) {
				$colorList[$activityModel->getId()] = \App\Modules\Settings\DataAccess\Models\Module::executeColorListHandlers('Calendar', $activityModel->getId(), $activityModel);
			}
			$viewer = $this->getViewer($request);
			$viewer->assign('RECORD', $recordModel);
			$viewer->assign('COLOR_LIST', $colorList);
			$viewer->assign('MODULE_NAME', $moduleName);
			$viewer->assign('PAGING_MODEL', $pagingModel);
			$viewer->assign('PAGE_NUMBER', $pageNumber);
			$viewer->assign('ACTIVITIES', $relatedActivities);
			$viewer->assign('DATA_TYPE', $type);
			$viewer->assign('IS_READ_ONLY', $request->getBoolean('isReadOnly'));
			return $viewer->view('RelatedActivities.tpl', $moduleName, true);
		}
	}

	/**
	 * Function returns related records based on related moduleName
	 * @param \App\Http\Vtiger_Request $request
	 * @return <type>
	 */
	public function showRelatedRecords(\App\Http\Vtiger_Request $request)
	{
		$parentId = $request->get('record');
		$pageNumber = $request->get('page');
		$limit = $request->get('limit');
		$searchParams = $request->get('search_params');
		$relatedModuleName = $request->get('relatedModule');
		$orderBy = $request->get('orderby');
		$sortOrder = $request->get('sortorder');
		$columns = $request->get('col');
		$moduleName = $request->getModule();
		$totalCount = $request->get('totalCount');
		if (empty($pageNumber)) {
			$pageNumber = 1;
		}

		$pagingModel = new \App\Modules\Base\Models\Paging();
		$pagingModel->set('page', $pageNumber);
		if (!empty($limit)) {
			$pagingModel->set('limit', $limit);
		} else {
			$pagingModel->set('limit', 10);
		}
		if ($sortOrder == 'ASC') {
			$nextSortOrder = 'DESC';
			$sortImage = 'glyphicon glyphicon-chevron-down';
		} else {
			$nextSortOrder = 'ASC';
			$sortImage = 'glyphicon glyphicon-chevron-up';
		}
		if (empty($orderBy) && empty($sortOrder)) {
			if (is_numeric($relatedModuleName))
				$relatedModuleName = \App\Utils\ModuleUtils::getModuleName($relatedModuleName);
			$relatedInstance = \App\Core\CRMEntity::getInstance($relatedModuleName);
			$orderBy = $relatedInstance->default_order_by;
			$sortOrder = $relatedInstance->default_sort_order;
		}
		$parentRecordModel = \App\Modules\Base\Models\Record::getInstanceById($parentId, $moduleName);
		$relationListView = \App\Modules\Base\Models\RelationListView::getInstance($parentRecordModel, $relatedModuleName);
		$relationModel = $relationListView->getRelationModel();
		
		// Set 'time' parameter on relation model if provided (for Calendar activities filtering)
		$time = $request->get('time');
		if (!empty($time) && $relatedModuleName === 'Calendar') {
			$relationModel->set('time', $time);
		}
		
		if ($relationModel->isFavorites() && \App\Modules\Users\Models\Privileges::isPermitted($moduleName, 'FavoriteRecords')) {
			$favorites = $relationListView->getFavoriteRecords();
			if (!empty($favorites)) {
				$relationListView->get('query_generator')->addNativeCondition(['vtiger_crmentity.crmid' => $favorites]);
			}
		}
		if (!empty($searchParams)) {
			$searchParams = $relationListView->get('query_generator')->parseBaseSearchParamsToCondition($searchParams);
			$relationListView->set('search_params', $searchParams);
		}
		if (!empty($orderBy)) {
			$relationListView->set('orderby', $orderBy);
			$relationListView->set('sortorder', $sortOrder);
		}
		$models = $relationListView->getEntries($pagingModel);
		$header = $relationListView->getHeaders();
		$links = $relationListView->getLinks();
		$relatedModuleModel = $relationModel->getRelationModuleModel();
		$relationField = $relationModel->getRelationField();
		$noOfEntries = count($models);

		if ($columns) {
			$header = array_splice($header, 0, $columns);
		}
		$colorList = [];
		foreach ($models as $record) {
			$colorList[$record->getId()] = \App\Modules\Settings\DataAccess\Models\Module::executeColorListHandlers($relatedModuleName, $record->getId(), $record);
		}
		$viewer = $this->getViewer($request);
		$viewer->assign('COLOR_LIST', $colorList);
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('MODULE_NAME', $relatedModuleName);
		$viewer->assign('LIMIT', $request->get('limit'));
		$viewer->assign('RELATED_RECORDS', $models);
		$viewer->assign('RELATED_HEADERS', $header);
		$viewer->assign('RELATED_MODULE', $relatedModuleModel);
		$viewer->assign('RELATED_MODULE_NAME', $relatedModuleName);
		$viewer->assign('PAGING_MODEL', $pagingModel);
		$viewer->assign('USER_MODEL', $request->getUser());

		$viewer->assign('PARENT_RECORD', $parentRecordModel);
		$viewer->assign('RELATED_LIST_LINKS', $links);
		$viewer->assign('RELATED_ENTIRES_COUNT', $noOfEntries);
		$viewer->assign('RELATION_FIELD', $relationField);
		if (\App\Core\AppConfig::performance('LISTVIEW_COMPUTE_PAGE_COUNT')) {
			$totalCount = $relationListView->getRelatedEntriesCount();
		}
		if (!empty($totalCount)) {
			$pagingModel->set('totalCount', (int) $totalCount);
			$viewer->assign('TOTAL_ENTRIES', (int) $totalCount);
		}
		$pageCount = $pagingModel->getPageCount();
		$startPaginFrom = $pagingModel->getStartPagingFrom();

		$viewer->assign('PAGE_COUNT', $pageCount);
		$viewer->assign('PAGE_NUMBER', $pageNumber);
		$viewer->assign('START_PAGIN_FROM', $startPaginFrom);
		$viewer->assign('PAGING_MODEL', $pagingModel);
		$viewer->assign('ORDER_BY', $orderBy);
		$viewer->assign('SORT_ORDER', $sortOrder);
		$viewer->assign('NEXT_SORT_ORDER', $nextSortOrder);
		$viewer->assign('SORT_IMAGE', $sortImage);
		$viewer->assign('COLUMN_NAME', $orderBy);
		$viewer->assign('COLUMNS', $columns);
		$viewer->assign('IS_EDITABLE', $relationModel->isEditable());
		$viewer->assign('IS_DELETABLE', $relationModel->isDeletable());
		$viewer->assign('SHOW_CREATOR_DETAIL', $relationModel->showCreatorDetail());
		$viewer->assign('SHOW_COMMENT', $relationModel->showComment());
		$viewer->assign('IS_READ_ONLY', $request->getBoolean('isReadOnly'));
		return $viewer->view('SummaryWidgets.tpl', $moduleName, true);
	}

	public function showRelatedTree(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$parentId = $request->get('record');
		$relatedModuleName = $request->get('relatedModule');

		$parentRecordModel = \App\Modules\Base\Models\Record::getInstanceById($parentId, $moduleName);
		$relationListView = \App\Modules\Base\Models\RelationListView::getInstance($parentRecordModel, $relatedModuleName);
		$relationModel = $relationListView->getRelationModel();

		$header = $relationListView->getTreeHeaders();
		$entries = $relationListView->getTreeEntries();

		$viewer = $this->getViewer($request);
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('RECORDID', $parentId);
		$viewer->assign('RELATED_MODULE_NAME', $relatedModuleName);
		$viewer->assign('RELATED_RECORDS', $entries);
		$viewer->assign('RELATED_HEADERS', $header);
		$viewer->assign('SHOW_CREATOR_DETAIL', (bool) $relationModel->get('creator_detail'));
		$viewer->assign('SHOW_COMMENT', (bool) $relationModel->get('relation_comment'));
		$viewer->assign('USER_MODEL', $request->getUser());
		$viewer->assign('IS_READ_ONLY', $request->getBoolean('isReadOnly'));
		return $viewer->view('RelatedTreeContent.tpl', $moduleName, true);
	}

	public function showRelatedProductsServices(\App\Http\Vtiger_Request $request)
	{
		$recordId = $request->get('record');
		$moduleName = $request->getModule();

		if (!$this->record) {
			$this->record = \App\Modules\Base\Models\DetailView::getInstance($moduleName, $recordId);
		}
		$recordModel = $this->record->getRecord();

		$detailViewLinkParams = array('MODULE' => $moduleName, 'RECORD' => $recordId);
		$detailViewLinks = $this->record->getDetailViewLinks($detailViewLinkParams);

		$viewer = $this->getViewer($request);
		$viewer->assign('RECORDID', $recordId);
		$viewer->assign('RECORD', $recordModel);

		$viewer->assign('DETAILVIEW_LINKS', $detailViewLinks);
		$viewer->assign('USER_MODEL', $request->getUser());
		$viewer->assign('IS_AJAX_ENABLED', $this->isAjaxEnabled($recordModel));
		$viewer->assign('MODULE_NAME', $moduleName);
		$viewer->assign('LIMIT', 'no_limit');
		if (!$this->recordStructure) {
			$this->recordStructure = \App\Modules\Base\Models\RecordStructure::getInstanceFromRecordModel($recordModel, \App\Modules\Base\Models\RecordStructure::RECORD_STRUCTURE_MODE_DETAIL);
		}
		$structuredValues = $this->recordStructure->getStructure();

		$moduleModel = $recordModel->getModule();

		$viewer->assign('RECORD_STRUCTURE', $structuredValues);
		$viewer->assign('BLOCK_LIST', $moduleModel->getBlocks());
		$viewer->assign('IS_READ_ONLY', $request->getBoolean('isReadOnly'));
		return $viewer->view('DetailViewProductsServicesContents.tpl', $moduleName, true);
	}

	/**
	 * Show recent relation
	 * @param \App\Http\Vtiger_Request $request
	 * @return string
	 */
	public function showRecentRelation(\App\Http\Vtiger_Request $request)
	{
		$pageNumber = $request->get('page');
		$limitPage = $request->get('limit');
		$moduleName = $request->getModule();

		if (empty($pageNumber)) {
			$pageNumber = 1;
		}
		if (empty($limitPage)) {
			$limitPage = 10;
		}
		$pagingModel = new \App\Modules\Base\Models\Paging();
		$pagingModel->set('page', $pageNumber);
		$pagingModel->set('limit', $limitPage);
		$config = \App\Modules\OSSMail\Models\Module::getComposeParameters();
		$histories = \App\Modules\Base\Widgets\HistoryRelation::getHistory($request, $pagingModel);
		$viewer = $this->getViewer($request);
		$viewer->assign('MODULE_NAME', $moduleName);
		$viewer->assign('RECORD_ID', $request->get('record'));
		$viewer->assign('HISTORIES', $histories);
		$viewer->assign('PAGING_MODEL', $pagingModel);
		$viewer->assign('POPUP', $config['popup']);
		$viewer->assign('NO_MORE', $request->get('noMore'));
		$viewer->assign('IS_READ_ONLY', $request->getBoolean('isReadOnly'));
		$viewer->assign('IS_FULLSCREEN', $request->getBoolean('isFullscreen'));
		return $viewer->view('HistoryRelation.tpl', $moduleName, true);
	}

	public function showOpenStreetMap($request)
	{
		$moduleName = $request->getModule();
		$recordId = $request->get('record');
		$coordinateModel = \App\Modules\OpenStreetMap\Models\Coordinate::getInstance();
		$coordinates = $coordinateModel->readCoordinates($recordId);
		$viewer = $this->getViewer($request);
		$viewer->assign('COORRDINATES', $coordinates);
		$viewer->assign('IS_READ_ONLY', $request->getBoolean('isReadOnly'));
		return $viewer->view('DetailViewMap.tpl', $moduleName, true);
	}
}
