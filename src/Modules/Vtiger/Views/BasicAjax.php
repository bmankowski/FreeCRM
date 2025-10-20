<?php

namespace App\Modules\Vtiger\Views;

/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * ********************************************************************************** */


use App\Http\Vtiger_Request;
class BasicAjax  extends \App\Modules\Vtiger\Views\Index
{

	public function __construct()
	{
		parent::__construct();
		$this->exposeMethod('showAdvancedSearch');
		$this->exposeMethod('showSearchResults');
	}

	public function checkPermission()
	{
		
	}

	public function preProcess(\App\Http\Vtiger_Request $request, $display = true)
	{
		return true;
	}

	public function postProcess(\App\Http\Vtiger_Request $request)
	{
		return true;
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$mode = $request->get('mode');
		if (!empty($mode)) {
			$this->invokeExposedMethod($mode, $request);
		}
		return;
	}

	/**
	 * Function to display the UI for advance search on any of the module
	 * @param \App\Http\Vtiger_Request $request
	 */
	public function showAdvancedSearch(\App\Http\Vtiger_Request $request)
	{
		//Modules for which search is excluded
		$excludedModuleForSearch = array('Vtiger', 'Reports');

		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();

		if ($request->get('source_module')) {
			$moduleName = $request->get('source_module');
		}

		$saveFilterPermitted = true;
		$saveFilterexcludedModules = array('ModComments', 'RSS', 'Portal', 'Integration', 'PBXManager', 'DashBoard');
		if (in_array($moduleName, $saveFilterexcludedModules)) {
			$saveFilterPermitted = false;
		}

		//See if it is an excluded module, If so search in home module
		if (in_array($moduleName, $excludedModuleForSearch)) {
			$moduleName = 'Home';
		}
		$module = $request->getModule();

		$customViewModel = new \App\Modules\CustomView\Models\Record();
		$customViewModel->setModule($moduleName);
		$moduleModel = \App\Modules\Vtiger\Models\Module::getInstance($moduleName);
		$recordStructureInstance = \App\Modules\Vtiger\Models\RecordStructure::getInstanceForModule($moduleModel);

		$viewer->assign('SEARCHABLE_MODULES', \App\Modules\Vtiger\Models\Module::getSearchableModules());
		$viewer->assign('CUSTOMVIEW_MODEL', $customViewModel);

		if ($moduleName === 'Calendar') {
			$advanceFilterOpsByFieldType = \App\Modules\Calendar\Models\Field::getAdvancedFilterOpsByFieldType();
		} else {
			$advanceFilterOpsByFieldType = \App\Modules\Vtiger\Models\Field::getAdvancedFilterOpsByFieldType();
		}
		$viewer->assign('ADVANCED_FILTER_OPTIONS', \App\CustomView::ADVANCED_FILTER_OPTIONS);
		$viewer->assign('ADVANCED_FILTER_OPTIONS_BY_TYPE', $advanceFilterOpsByFieldType);
		$viewer->assign('DATE_FILTERS', Vtiger_AdvancedFilter_Helper::getDateFilter($module));
		$viewer->assign('RECORD_STRUCTURE', $recordStructureInstance->getStructure());
		$viewer->assign('SOURCE_MODULE', $moduleName);
		$viewer->assign('SOURCE_MODULE_MODEL', $moduleModel);
		$viewer->assign('MODULE', $module);
		$viewer->assign('SAVE_FILTER_PERMITTED', $saveFilterPermitted);
		$viewer->assign('USER_MODEL', \App\Modules\Users\Models\Record::getCurrentUserModel());
		echo $viewer->view('AdvanceSearch.tpl', $moduleName, true);
	}

	/**
	 * Function to display the Search Results
	 * @param \App\Http\Vtiger_Request $request
	 */
	public function showSearchResults(\App\Http\Vtiger_Request $request)
	{
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		$advFilterList = $request->get('advfilterlist');

		//used to show the save modify filter option
		$isAdvanceSearch = false;
		$matchingRecords = [];
		if (is_array($advFilterList) && $advFilterList) {
			$isAdvanceSearch = true;
			$queryGenerator = new \App\QueryGenerator($moduleName);
			$queryGenerator->setFields(['id']);
			$queryGenerator->parseAdvFilter($advFilterList);
			$query = $queryGenerator->createQuery();
			$rows = $query->limit(100)->all();
			foreach ($rows as &$row) {
				$recordId = current($row);
				$recordModel = \App\Modules\Vtiger\Models\Record::getInstanceById($recordId);
				$recordModel->set('permitted', true);
				$matchingRecords[$moduleName][$recordId] = $recordModel;
			}
			$viewer->assign('SEARCH_MODULE', $moduleName);
		} else {
			$searchKey = $request->get('value');
			$limit = $request->get('limit') != 'false' ? $request->get('limit') : false;
			$operator = (!$request->isEmpty('operator') ) ? $request->get('operator') : false;
			$searchModule = false;
			if ($request->get('searchModule')) {
				$searchModule = $request->get('searchModule');
			}
			$viewer->assign('SEARCH_KEY', $searchKey);
			$viewer->assign('SEARCH_MODULE', $searchModule);
			$matchingRecords = \App\Modules\Vtiger\Models\Record::getSearchResult($searchKey, $searchModule, $limit, $operator);
			if (\App\AppConfig::search('GLOBAL_SEARCH_SORTING_RESULTS') === 1) {
				$matchingRecordsList = [];
				foreach (\App\Module::getAllEntityModuleInfo(true) as &$module) {
					if (isset($matchingRecords[$module['modulename']]) && $module['turn_off'] == 1) {
						$matchingRecordsList[$module['modulename']] = $matchingRecords[$module['modulename']];
					}
				}
				$matchingRecords = $matchingRecordsList;
			}
		}
		$curentModule = $request->get('curentModule');
		if (\App\AppConfig::search('GLOBAL_SEARCH_CURRENT_MODULE_TO_TOP') && isset($matchingRecords[$curentModule])) {
			$pushTop = $matchingRecords[$curentModule];
			unset($matchingRecords[$curentModule]);
			$matchingRecords = [$curentModule => $pushTop] + $matchingRecords;
		}
		if ($request->get('html') === 'true') {
			$viewer->assign('MODULE', $moduleName);
			$viewer->assign('MATCHING_RECORDS', $matchingRecords);
			$viewer->assign('IS_ADVANCE_SEARCH', $isAdvanceSearch);
			echo $viewer->view('UnifiedSearchResults.tpl', '', true);
		} else {
			$recordsList = [];
			foreach ($matchingRecords as $module => &$modules) {
				foreach ($modules as $recordID => &$recordModel) {
					$label = \App\Utils\ListViewUtils::decodeHtml($recordModel->getName());
					$label .= ' (' . \App\Fields\Owner::getLabel($recordModel->get('smownerid')) . ')';
					if (!$recordModel->get('permitted')) {
						$label .= ' <span class="glyphicon glyphicon-warning-sign" aria-hidden="true"></span>';
					}
					$recordsList[] = [
						'id' => $recordID,
						'module' => $module,
						'category' => \App\Runtime\Vtiger_Language_Handler::translate($module, $module),
						'label' => $label,
						'permitted' => $recordModel->get('permitted'),
					];
				}
			}
			$response = new \App\Http\Vtiger_Response();
			$response->setResult($recordsList);
			$response->emit();
		}
	}
}
