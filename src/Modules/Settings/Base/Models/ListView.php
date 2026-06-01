<?php

namespace App\Modules\Settings\Base\Models;


/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * *********************************************************************************** */

/*
 * Settings List View Model Class
 */

class ListView extends \App\Runtime\BaseModel
{
	/**
	 * @var \App\Modules\Settings\Base\Models\Module|\App\Modules\Base\Models\Module
	 */
	protected $module;

	public function getModule()
	{
		return $this->module;
	}

	public function setModule(string $name): self
	{
		$modelClassName = \App\Core\Loader::getComponentClassName('Model', 'Module', $name);
		$this->module = new $modelClassName();
		return $this;
	}

	public function setModuleFromInstance(\App\Modules\Settings\Base\Models\Module $module): self
	{
		$this->module = $module;
		return $this;
	}

	/**
	 * Function to get the list view header
	 * @return array - List of \App\Modules\Base\Models\Field instances
	 */
	public function getListViewHeaders(): array 
	{
		$module = $this->getModule();
		return $module->getListFields();
	}

	public function getBasicListQuery()
	{
		$module = $this->getModule();
		return (new \App\Db\Query())->from($module->getBaseTable());
	}

	/**
	 * Function to get the list view entries
	 * @param \App\Modules\Base\Models\Paging $pagingModel
	 * @return \App\Modules\Settings\Base\Models\Record[] - Associative array of record id mapped to \App\Modules\Base\Models\Record instance.
	 */
	public function getListViewEntries($pagingModel)
	{
		$moduleModel = $this->getModule();
		$moduleName = $moduleModel->getName();
		$parentModuleName = $moduleModel->getParentName();
		$qualifiedModuleName = $moduleName;
		if (!empty($parentModuleName)) {
			$qualifiedModuleName = $parentModuleName . ':' . $qualifiedModuleName;
		}
		$recordModelClass = \App\Core\Loader::getComponentClassName('Model', 'Record', $qualifiedModuleName);
		$listQuery = $this->getBasicListQuery();

		$startIndex = $pagingModel->getStartIndex();
		$pageLimit = $pagingModel->getPageLimit();

		$orderBy = $this->getForSql('orderby');
		if (!empty($orderBy) && $orderBy === 'smownerid') {
			$fieldModel = \App\Modules\Base\Models\Field::getInstance('assigned_user_id', $moduleModel);
			if ($fieldModel->getFieldDataType() == 'owner') {
				$orderBy = 'COALESCE(' . \App\Utils\ModuleUtils::getSqlForNameInDisplayFormat('Users') . ',vtiger_groups.groupname)';
			}
		}
		if (!empty($orderBy) && !$moduleModel->isVirtualListField($orderBy)) {
			if ($this->getForSql('sortorder') === 'DASC') {
				$listQuery->orderBy([$orderBy => SORT_DESC]);
			} else {
				$listQuery->orderBy([$orderBy => SORT_ASC]);
			}
		}
		if ($moduleModel->isPagingSupported()) {
			$listQuery->limit($pageLimit)->offset($startIndex);
		}
		$dataReader = $listQuery->createCommand()->query();
		$listViewRecordModels = [];
		while ($row = $dataReader->read()) {
			$record = new $recordModelClass();
			$record->setData($row);
			if (method_exists($record, 'getModule') && method_exists($record, 'setModule')) {
				$record->setModule($moduleModel);
			}
			$listViewRecordModels[$record->getId()] = $record;
		}
		if ($moduleModel->isPagingSupported()) {
			$pagingModel->calculatePageRange($dataReader->count());
		}
		return $listViewRecordModels;
	}

	public function getListViewLinks()
	{
		$links = [];
		// Initialize LISTVIEWBASIC key to prevent undefined array key warnings in templates
		$links['LISTVIEWBASIC'] = [];
		$basicLinks = $this->getBasicLinks();

		foreach ($basicLinks as $basicLink) {
			$links['LISTVIEWBASIC'][] = \App\Modules\Base\Models\Link::getInstanceFromValues($basicLink);
		}
		return $links;
	}

	/**
	 * Function to get Basic links
	 * @return array of Basic links
	 */
	public function getBasicLinks()
	{
		$basicLinks = [];
		$moduleModel = $this->getModule();
		if ($moduleModel->hasCreatePermissions())
			$basicLinks[] = [
				'linktype' => 'LISTVIEWBASIC',
				'linklabel' => 'LBL_ADD_RECORD',
				'linkurl' => $moduleModel->getCreateRecordUrl(),
				'linkclass' => 'btn-success addButton',
				'linkicon' => 'glyphicon glyphicon-plus',
				'showLabel' => 1
			];

		return $basicLinks;
	}
	/*	 * * 
	 * Function which will get the list view count  
	 * @return - number of records 
	 */

	public function getListViewCount()
	{
		$listQuery = $this->getBasicListQuery();
		return $listQuery->count();
	}

	/**
	 * Function to get the instance of Settings module model
	 * @return \App\Modules\Settings\Base\Models\Module instance
	 */
	public static function getInstance($name = 'Settings:Vtiger')
	{
		$modelClassName = \App\Core\Loader::getComponentClassName('Model', 'ListView', $name);
		$instance = new $modelClassName();
		return $instance->setModule($name);
	}
}
