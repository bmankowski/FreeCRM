<?php

namespace App\Modules\Vtiger\Models;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * *********************************************************************************** */

/**
 * Vtiger ListView Model Class
 */
class ListView extends \App\Runtime\BaseModel
{

	/**
	 * Function to get the Module Model
	 * @return \App\Modules\Vtiger\Models\Module instance
	 */
	public function getModule()
	{
		return $this->get('module');
	}

	/**
	 * Static Function to get the Instance of Vtiger ListView model for a given module and custom view
	 * @param string $moduleName - Module Name
	 * @param int $viewId - Custom View Id
	 * @return \App\Modules\Vtiger\Models\ListView instance
	 */
	public static function getInstance($moduleName, $viewId = 0)
	{
		$cacheName = $viewId . ':' . $moduleName;
		if (\App\Cache\Cache::has('ListView_Model', $cacheName)) {
			return \App\Cache\Cache::get('ListView_Model', $cacheName);
		}
		$modelClassName = \App\Loader::getComponentClassName('Model', 'ListView', $moduleName);
		$instance = new $modelClassName();
		$moduleModel = \App\Modules\Vtiger\Models\Module::getInstance($moduleName);
		$queryGenerator = new \App\QueryGenerator($moduleModel->get('name'));
		if ($viewId) {
			$queryGenerator->initForCustomViewById($viewId);
		} else {
			if (!$queryGenerator->initForDefaultCustomView()) {
				$queryGenerator->loadListFields();
			}
		}
		$instance->set('module', $moduleModel)->set('query_generator', $queryGenerator);
		\App\Cache\Cache::get('ListView_Model', $cacheName, $instance);
		return $instance;
	}

	/**
	 * Static Function to get the Instance of Vtiger ListView model for a given module and custom view
	 * @param string $value - Module Name
	 * @return \App\Modules\Vtiger\Models\ListView instance
	 */
	public static function getInstanceForPopup($value, $sourceModule = false)
	{
		$modelClassName = \App\Loader::getComponentClassName('Model', 'ListView', $value);
		$instance = new $modelClassName();
		$moduleModel = \App\Modules\Vtiger\Models\Module::getInstance($value);
		$queryGenerator = new \App\QueryGenerator($moduleModel->get('name'));
		if (!$sourceModule && !empty($sourceModule)) {
			$moduleModel->set('sourceModule', $sourceModule);
		}
		$listFields = $moduleModel->getPopupViewFieldsList($sourceModule);
		$listFields[] = 'id';
		$queryGenerator->setFields($listFields);
		return $instance->set('module', $moduleModel)->set('query_generator', $queryGenerator);
	}

	/**
	 * Function to get the Quick Links for the List view of the module
	 * @param array $linkParams
	 * @return array List of \App\Modules\Vtiger\Models\Link instances
	 */
	public function getHederLinks($linkParams)
	{
		$links = \App\Modules\Vtiger\Models\Link::getAllByType($this->getModule()->getId(), ['LIST_VIEW_HEADER'], $linkParams);

		$headerLinks = [];
		$moduleModel = $this->getModule();
		if (\App\AppConfig::module('ModTracker', 'WATCHDOG') && $moduleModel->isPermitted('WatchingModule')) {
			$watchdog = Watchdog::getInstance($moduleModel->getName());
			$class = 'btn-default';
			if ($watchdog->isWatchingModule()) {
				$class = 'btn-info';
			}
			$headerLinks[] = [
				'linktype' => 'LIST_VIEW_HEADER',
				'linkhint' => 'BTN_WATCHING_MODULE',
				'linkurl' => 'javascript:Vtiger_Index_Js.changeWatching(this)',
				'linkclass' => $class,
				'linkicon' => 'glyphicon glyphicon-eye-open',
				'linkdata' => ['off' => 'btn-default', 'on' => 'btn-info', 'value' => $watchdog->isWatchingModule() ? 0 : 1],
				'active' => !$watchdog->isLock()
			];
		}
		$userPrivilegesModel = \App\Modules\Users\Models\Privileges::getCurrentUserPrivilegesModel();
		if ($userPrivilegesModel->hasModuleActionPermission('Notification', 'CreateView')) {
			$headerLinks[] = [
				'linktype' => 'LIST_VIEW_HEADER',
				'linkhint' => 'LBL_SEND_NOTIFICATION',
				'linkurl' => 'javascript:Vtiger_Index_Js.sendNotification(this)',
				'linkicon' => 'glyphicon glyphicon-send'
			];
		}
		$openStreetMapModuleModel = \App\Modules\Vtiger\Models\Module::getInstance('OpenStreetMap');
		if ($userPrivilegesModel->hasModulePermission($openStreetMapModuleModel->getId()) && $openStreetMapModuleModel->isAllowModules($moduleModel->getName())) {
			$headerLinks[] = [
				'linktype' => 'LIST_VIEW_HEADER',
				'linkhint' => 'LBL_SHOW_MAP',
				'linkurl' => 'javascript:Vtiger_List_Js.showMap()',
				'linkicon' => 'fa fa-globe'
			];
		}
		foreach ($headerLinks as $headerLink) {
			$links['LIST_VIEW_HEADER'][] = \App\Modules\Vtiger\Models\Link::getInstanceFromValues($headerLink);
		}
		return $links;
	}

	/**
	 * Function to get the list of listview links for the module
	 * @param <Array> $linkParams
	 * @return <Array> - Associate array of Link Type to List of \App\Modules\Vtiger\Models\Link instances
	 */
	public function getListViewLinks($linkParams)
	{
		$moduleModel = $this->getModule();
		$links = [];

		$basicLinks = $this->getBasicLinks();
		foreach ($basicLinks as $basicLink) {
			$links['LISTVIEWBASIC'][] = \App\Modules\Vtiger\Models\Link::getInstanceFromValues($basicLink);
		}

		$allLinks = \App\Modules\Vtiger\Models\Link::getAllByType($moduleModel->getId(), ['LISTVIEWBASIC', 'LISTVIEW'], $linkParams);
		if (!empty($allLinks)) {
			foreach ($allLinks as $type => $allLinksByType) {
				foreach ($allLinksByType as $linkModel) {
					$links[$type][] = $linkModel;
				}
			}
		}

		$advancedLinks = $this->getAdvancedLinks();
		foreach ($advancedLinks as $advancedLink) {
			$links['LISTVIEW'][] = \App\Modules\Vtiger\Models\Link::getInstanceFromValues($advancedLink);
		}
		return $links;
	}

	/**
	 * Function to get the list of Mass actions for the module
	 * @param <Array> $linkParams
	 * @return <Array> - Associative array of Link type to List of  \App\Modules\Vtiger\Models\Link instances for Mass Actions
	 */
	public function getListViewMassActions($linkParams)
	{
		$currentUser = \App\Modules\Users\Models\Record::getCurrentUserModel();
		$moduleModel = $this->getModule();
		$links = \App\Modules\Vtiger\Models\Link::getAllByType($moduleModel->getId(), ['LISTVIEWMASSACTION'], $linkParams);
		$massActionLinks = [];
		if ($moduleModel->isPermitted('MassEdit')) {
			$massActionLinks[] = array(
				'linktype' => 'LISTVIEWMASSACTION',
				'linklabel' => 'LBL_MASS_EDIT',
				'linkurl' => 'javascript:Vtiger_List_Js.triggerMassEdit("index.php?module=' . $moduleModel->get('name') . '&view=MassActionAjax&mode=showMassEditForm");',
				'linkicon' => ''
			);
		}
		if ($moduleModel->isPermitted('MassDelete')) {
			$massActionLinks[] = array(
				'linktype' => 'LISTVIEWMASSACTION',
				'linklabel' => 'LBL_MASS_DELETE',
				'linkurl' => 'javascript:Vtiger_List_Js.massDeleteRecords("index.php?module=' . $moduleModel->get('name') . '&action=MassDelete");',
				'linkicon' => ''
			);
		}
		$modCommentsModel = \App\Modules\Vtiger\Models\Module::getInstance('ModComments');
		if ($moduleModel->isCommentEnabled() && $modCommentsModel->isPermitted('EditView') && $moduleModel->isPermitted('MassAddComment')) {
			$massActionLinks[] = array(
				'linktype' => 'LISTVIEWMASSACTION',
				'linklabel' => 'LBL_MASS_ADD_COMMENT',
				'linkurl' => 'index.php?module=' . $moduleModel->get('name') . '&view=MassActionAjax&mode=showAddCommentForm',
				'linkicon' => ''
			);
		}

		if ($moduleModel->isPermitted('MassTransferOwnership')) {
			$massActionLinks[] = array(
				'linktype' => 'LISTVIEWMASSACTION',
				'linklabel' => 'LBL_TRANSFER_OWNERSHIP',
				'linkurl' => 'javascript:Vtiger_List_Js.triggerTransferOwnership("index.php?module=' . $moduleModel->getName() . '&view=MassActionAjax&mode=transferOwnership")',
				'linkicon' => ''
			);
		}
		if ($moduleModel->isTrackingEnabled() && \App\AppConfig::module('ModTracker', 'UNREVIEWED_COUNT') && $moduleModel->isPermitted('ReviewingUpdates') && $currentUser->getId() === $currentUser->getRealId()) {
			$massActionLinks[] = [
				'linktype' => 'LISTVIEWMASSACTION',
				'linklabel' => 'LBL_REVIEW_CHANGES',
				'linkurl' => 'javascript:Vtiger_List_Js.triggerReviewChanges("index.php?module=ModTracker&sourceModule=' . $moduleModel->getName() . '&action=ChangesReviewedOn&mode=reviewChanges")',
				'linkicon' => ''
			];
		}
		foreach ($massActionLinks as $massActionLink) {
			$links['LISTVIEWMASSACTION'][] = \App\Modules\Vtiger\Models\Link::getInstanceFromValues($massActionLink);
		}
		return $links;
	}

	/**
	 * Get query generator instance
	 * @return \App\QueryGenerator
	 */
	public function getQueryGenerator()
	{
		return $this->get('query_generator');
	}

	/**
	 * Function to get the list view header
	 * @return array - List of \App\Modules\Vtiger\Models\Field instances
	 */
	public function getListViewHeaders()
	{
		$headerFieldModels = [];
		$headerFields = $this->getQueryGenerator()->getListViewFields();
		foreach ($headerFields as $fieldName => &$fieldsModel) {
			if ($fieldsModel && (!$fieldsModel->isViewable() || !$fieldsModel->getPermissions())) {
				continue;
			}
			$headerFieldModels[$fieldName] = $fieldsModel;
		}
		return $headerFieldModels;
	}

	/**
	 * Set list view order by
	 */
	public function loadListViewOrderBy()
	{
		$orderBy = $this->getForSql('orderby');
		if (!empty($orderBy)) {
			$field = $this->getModule()->getFieldByColumn($orderBy);
			if ($field) {
				$orderBy = $field->getName();
			}
			if ($field || $orderBy === 'id') {
				return $this->getQueryGenerator()->setOrder($orderBy, $this->getForSql('sortorder'));
			}
			\App\Log::warning("[ListView] Incorrect value of sorting: '$orderBy'");
		}
	}

	/**
	 * Load list view conditions
	 */
	public function loadListViewCondition()
	{
		$queryGenerator = $this->getQueryGenerator();
		$srcRecord = $this->get('src_record');
		if ($this->getModule()->get('name') === $this->get('src_module') && !empty($srcRecord)) {
			$queryGenerator->addCondition('id', $srcRecord, 'n');
		}
		$searchParams = $this->get('search_params');
		if ($searchParams) {
			$queryGenerator->parseAdvFilter($searchParams);
		}
		$searchKey = $this->get('search_key');
		$searchValue = $this->get('search_value');
		$operator = $this->get('operator');
		if ($searchKey) {
			if ($operator === 's' && strlen($searchValue) === 1) {
				$searchValue = [$searchValue, strtolower($searchValue)];
			}
			$queryGenerator->addBaseSearchConditions($searchKey, $searchValue, $operator);
		}
		$searchResult = $this->get('searchResult');
		if ($searchResult && is_array($searchResult)) {
			$queryGenerator->addNativeCondition(['vtiger_crmentity.crmid' => $searchResult]);
		}
		$sourceModule = $this->get('src_module');
		if ($sourceModule) {
			$moduleModel = $this->getModule();
			if (method_exists($moduleModel, 'getQueryByModuleField')) {
				$moduleModel->getQueryByModuleField($sourceModule, $this->get('src_field'), $this->get('src_record'), $queryGenerator);
			}
			if (method_exists($moduleModel, 'getQueryByRelatedField')) {
				$moduleModel->getQueryByRelatedField($this, $queryGenerator);
			}
		}
	}

	/**
	 * Function to get the list view entries
	 * @param \App\Modules\Vtiger\Models\Paging $pagingModel
	 * @return \App\Modules\Vtiger\Models\Record[] - Associative array of record id mapped to \App\Modules\Vtiger\Models\Record instance.
	 */
	public function getListViewEntries(\App\Modules\Vtiger\Models\Paging $pagingModel)
	{
		$moduleModel = $this->getModule();
		$this->loadListViewCondition();
		$this->loadListViewOrderBy();
		$pageLimit = $pagingModel->getPageLimit();
		$query = $this->getQueryGenerator()->createQuery();
		if ($pagingModel->get('limit') !== 'no_limit') {
			$query->limit($pageLimit + 1)->offset($pagingModel->getStartIndex());
		}
		$rows = $query->all();
		$count = count($rows);
		$pagingModel->calculatePageRange($count);
		if ($count > $pageLimit) {
			array_pop($rows);
			$pagingModel->set('nextPageExists', true);
		} else {
			$pagingModel->set('nextPageExists', false);
		}
		$listViewRecordModels = [];
		foreach ($rows as &$row) {
			$recordModel = $moduleModel->getRecordFromArray($row);
			$recordModel->colorList = \App\Modules\Settings\DataAccess\Models\Module::executeColorListHandlers($moduleModel->get('name'), $row['id'], $recordModel);
			$listViewRecordModels[$row['id']] = $recordModel;
		}
		unset($rows);

		return $listViewRecordModels;
	}

	/**
	 * Function to get the list view entries
	 * @param \App\Modules\Vtiger\Models\Paging $pagingModel
	 * @return array - Associative array of record id mapped to \App\Modules\Vtiger\Models\Record instance.
	 */
	public function getListViewCount()
	{
		$this->loadListViewCondition();
		return $this->getQueryGenerator()->createQuery()->count();
	}

	/**
	 * Function to give advance links of a module
	 * @return array of advanced links
	 */
	public function getAdvancedLinks()
	{
		$moduleModel = $this->getModule();
		$advancedLinks = [];

		if ($moduleModel->isPermitted('CreateView') && $moduleModel->isPermitted('Import')) {
			$advancedLinks[] = [
				'linktype' => 'LISTVIEW',
				'linklabel' => 'LBL_IMPORT',
				'linkurl' => $moduleModel->getImportUrl(),
				'linkicon' => ''
			];
		}
		if ($moduleModel->isPermitted('Export')) {
			$advancedLinks[] = [
				'linktype' => 'LISTVIEW',
				'linklabel' => 'LBL_EXPORT',
				'linkurl' => 'javascript:Vtiger_List_Js.triggerExportAction("' . $this->getModule()->getExportUrl() . '")',
				'linkicon' => ''
			];
		}
		if (!\App\Modules\Settings\ModuleManager\Models\Library::checkLibrary('mPDF') && $moduleModel->isPermitted('ExportPdf')) {
			$handlerClass = \App\Loader::getComponentClassName('Model', 'PDF', $moduleModel->getName());
			$pdfModel = new $handlerClass();
			$templates = $pdfModel->getActiveTemplatesForModule($moduleModel->getName(), 'List');
			if (count($templates) > 0) {
				$advancedLinks[] = [
					'linktype' => 'DETAILVIEWBASIC',
					'linklabel' => \App\Runtime\Vtiger_Language_Handler::translate('LBL_EXPORT_PDF'),
					'linkurl' => 'javascript:Vtiger_Header_Js.getInstance().showPdfModal("index.php?module=' . $moduleModel->getName() . '&view=PDF&fromview=List");',
					'linkicon' => 'glyphicon glyphicon-save-file',
					'title' => \App\Runtime\Vtiger_Language_Handler::translate('LBL_EXPORT_PDF')
				];
			}
		}
		if ($moduleModel->isPermitted('DuplicatesHandling')) {
			$advancedLinks[] = [
				'linktype' => 'LISTVIEWMASSACTION',
				'linklabel' => 'LBL_FIND_DUPLICATES',
				'linkurl' => 'Javascript:Vtiger_List_Js.showDuplicateSearchForm("index.php?module=' . $moduleModel->getName() .
				'&view=MassActionAjax&mode=showDuplicatesSearchForm")',
				'linkicon' => ''
			];
		}
		if ($moduleModel->isPermitted('QuickExportToExcel') && !\App\Modules\Settings\ModuleManager\Models\Library::checkLibrary('PHPExcel')) {
			$advancedLinks[] = [
				'linktype' => 'LISTVIEWMASSACTION',
				'linklabel' => 'LBL_QUICK_EXPORT_TO_EXCEL',
				'linkurl' => 'javascript:Vtiger_List_Js.triggerQuickExportToExcel("' . $moduleModel->getName() . '")',
				'linkicon' => ''
			];
		}
		if ($moduleModel->isPermitted('RecordMappingList')) {
			$handlerClass = \App\Loader::getComponentClassName('Model', 'MappedFields', $moduleModel->getName());
			$mfModel = new $handlerClass();
			$templates = $mfModel->getActiveTemplatesForModule($moduleModel->getName(), 'List');
			if (count($templates) > 0) {
				$advancedLinks[] = [
					'linktype' => 'LISTVIEW',
					'linklabel' => 'LBL_GENERATE_RECORDS',
					'linkurl' => 'javascript:Vtiger_List_Js.triggerGenerateRecords("index.php?module=' . $moduleModel->getName() . '&view=GenerateModal&fromview=List");',
				];
			}
		}
		return $advancedLinks;
	}

	/**
	 * Function to get Basic links
	 * @return array of Basic links
	 */
	public function getBasicLinks()
	{
		$basicLinks = [];
		$moduleModel = $this->getModule();

		if ($moduleModel->isPermitted('CreateView')) {
			$basicLinks[] = [
				'linktype' => 'LISTVIEWBASIC',
				'linklabel' => 'LBL_ADD_RECORD',
				'linkurl' => $moduleModel->getCreateRecordUrl(),
				'linkclass' => 'addButton moduleColor_' . $moduleModel->getName(),
				'linkicon' => 'glyphicon glyphicon-plus',
				'showLabel' => 1,
				'linkhref' => true
			];
		}

		if (!\App\Modules\Settings\ModuleManager\Models\Library::checkLibrary('mPDF') && $moduleModel->isPermitted('ExportPdf')) {
			$handlerClass = \App\Loader::getComponentClassName('Model', 'PDF', $moduleModel->getName());
			$pdfModel = new $handlerClass();
			$templates = $pdfModel->getActiveTemplatesForModule($moduleModel->getName(), 'List');
			if (count($templates) > 0) {
				$basicLinks[] = [
					'linktype' => 'LISTVIEWBASIC',
					'linkurl' => 'javascript:Vtiger_Header_Js.getInstance().showPdfModal("index.php?module=' . $moduleModel->getName() . '&view=PDF&fromview=List");',
					'linkicon' => 'glyphicon glyphicon-save-file',
					'linkhint' => \App\Runtime\Vtiger_Language_Handler::translate('LBL_EXPORT_PDF')
				];
			}
		}
		return $basicLinks;
	}

	public function extendPopupFields($fieldsList)
	{
		$moduleModel = $this->get('module');
		$listFields = $moduleModel->getPopupViewFieldsList();
		$this->getQueryGenerator()->setFields($listFields);
	}
}
