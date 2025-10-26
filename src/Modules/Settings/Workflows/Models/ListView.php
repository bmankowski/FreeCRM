<?php

namespace App\Modules\Settings\Workflows\Models;


/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

/*
 * Settings List View Model Class
 */

class ListView extends \App\Modules\Settings\Base\Models\ListView
{

	/**
	 * Function to get the list view entries
	 * @param \App\Modules\Base\Models\Paging $pagingModel
	 * @return <Array> - Associative array of record id mapped to \App\Modules\Base\Models\Record instance.
	 */
	public function getListViewEntries($pagingModel)
	{
		$module = $this->getModule();
		$moduleName = $module->getName();
		$parentModuleName = $module->getParentName();
		$qualifiedModuleName = $moduleName;
		if (!empty($parentModuleName)) {
			$qualifiedModuleName = $parentModuleName . ':' . $qualifiedModuleName;
		}
		$recordModelClass = \App\Loader::getComponentClassName('Model', 'Record', $qualifiedModuleName);

		$listFields = $module->listFields;
		unset($listFields['all_tasks']);
		unset($listFields['active_tasks']);
		$listFields = array_keys($listFields);
		$listFields [] = $module->baseIndex;
		$listQuery = (new \App\Db\Query())->select($listFields)
			->from($module->baseTable);

		$sourceModule = $this->get('sourceModule');
		if (!empty($sourceModule)) {
			$listQuery->where(['module_name' => $sourceModule]);
		}
		$startIndex = $pagingModel->getStartIndex();
		$pageLimit = $pagingModel->getPageLimit();

		$orderBy = $this->getForSql('orderby');
		if (!empty($orderBy) && $orderBy === 'smownerid') {
			$fieldModel = \App\Modules\Base\Models\Field::getInstance('assigned_user_id', $moduleModel);
			if ($fieldModel->getFieldDataType() == 'owner') {
				$orderBy = 'COALESCE(' . \App\Module::getSqlForNameInDisplayFormat('Users') . ',vtiger_groups.groupname)';
			}
		}
		if (!empty($orderBy)) {
			$listQuery->orderBy(sprintf('%s %s', $orderBy, $this->getForSql('sortorder')));
		}
		$listQuery->limit($pageLimit + 1)->offset($startIndex);

		$dataReader = $listQuery->createCommand()->query();
		$listViewRecordModels = [];
		while ($row = $dataReader->read()) {
			$record = new $recordModelClass();
			$module_name = $row['module_name'];

			//To handle translation of calendar to To Do
			if ($module_name == 'Calendar') {
				$module_name = \App\Runtime\Vtiger_Language_Handler::translate('LBL_TASK', $module_name);
			} else {
				$module_name = \App\Runtime\Vtiger_Language_Handler::translate($module_name, $module_name);
			}
			$workflowModel = $record->getInstance($row['workflow_id']);
			$taskList = $workflowModel->getTasks();
			$row['module_name'] = $module_name;
			$row['execution_condition'] = \App\Runtime\Vtiger_Language_Handler::translate($record->executionConditionAsLabel($row['execution_condition']), 'Settings:Workflows');
			$row['summary'] = \App\Runtime\Vtiger_Language_Handler::translate($row['summary'], 'Settings:Workflows');
			$row['all_tasks'] = count($taskList);
			$row['active_tasks'] = $workflowModel->getActiveCountFromRecord($taskList);

			$record->setData($row);
			$listViewRecordModels[$record->getId()] = $record;
		}

		$pagingModel->calculatePageRange($dataReader->count());
		if ($dataReader->count() > $pageLimit) {
			$pagingModel->set('nextPageExists', true);
		} else {
			$pagingModel->set('nextPageExists', false);
		}

		return $listViewRecordModels;
	}
	/*	 * *
	 * Function which will get the list view count
	 * @return - number of records
	 */

	public function getListViewCount()
	{
		$module = $this->getModule();
		$query = (new \App\Db\Query())->from($module->baseTable);
		$sourceModule = $this->get('sourceModule');
		if ($sourceModule) {
			$query->where(['module_name' => $sourceModule]);
		}
		return $query->count();
	}
}
