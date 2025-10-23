<?php

namespace App\Modules\Import\Models;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

/**
 * Vtiger ListView Model Class
 */
class ListView extends \App\Modules\Vtiger\Models\ListView
{

	/**
	 * Function to get the list of listview links for the module
	 * @param <Array> $linkParams
	 * @return false - no List View Links needed on Import pages
	 */
	public function getListViewLinks($linkParams)
	{
		return false;
	}

	/**
	 * Function to get the list of Mass actions for the module
	 * @param <Array> $linkParams
	 * @return false - no List View Links needed on Import pages
	 */
	public function getListViewMassActions($linkParams)
	{
		return false;
	}

	/**
	 * Function to get the list view entries
	 * @param \App\Modules\Vtiger\Models\Paging $pagingModel
	 * @return array - Associative array of record id mapped to \App\Modules\Vtiger\Models\Record instance.
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
		$query = $this->addLastImportedRecordConditions($query);
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
	 * ListView count
	 * @return int
	 */
	public function getListViewCount()
	{
		$this->loadListViewCondition();
		$query = $this->getQueryGenerator()->createQuery();
		$query = $this->addLastImportedRecordConditions($query);
		return $query->count();
	}

	/**
	 * Static Function to get the Instance of Vtiger ListView model for a given module and custom view
	 * @param string $moduleName - Module Name
	 * @param int $viewId - Custom View Id
	 * @return \App\Modules\Vtiger\Models\ListView instance
	 */
	public static function getInstance($moduleName, $viewId = '0')
	{
		$modelClassName = \App\Loader::getComponentClassName('Model', 'ListView', 'Import');
		$instance = new $modelClassName();
		$moduleModel = \App\Modules\Vtiger\Models\Module::getInstance($moduleName);
		$queryGenerator = new \App\QueryGenerator($moduleModel->get('name'));
		$queryGenerator->initForDefaultCustomView(true);
		return $instance->set('module', $moduleModel)->set('query_generator', $queryGenerator);
	}

	/**
	 * Function adds conditions to query
	 * @param \App\Db\Query $query
	 * @return \App\Db\Query
	 */
	public function addLastImportedRecordConditions($query)
	{
		$moduleModel = $this->getModule();
		$user = \App\User::getCurrentUserId();
		$userDBTableName = \App\Modules\Import\Models\Module::getDbTableName($user);
		$query->innerJoin($userDBTableName, $moduleModel->basetable . '.' . $moduleModel->basetableid . " = $userDBTableName.recordid");
		$query->where(['and', ['not', [$userDBTableName . '.temp_status' => [\App\Modules\Import\Actions\Data::IMPORT_RECORD_FAILED, \App\Modules\Import\Actions\Data::IMPORT_RECORD_SKIPPED]]], ['not', [$userDBTableName . '.recordid' => null]]]);
		return $query;
	}
}
