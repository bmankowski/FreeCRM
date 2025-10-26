<?php

namespace App\Modules\Settings\TreesManager\Models;


/* +***********************************************************************************************************************************
 * The contents of this file are subject to the YetiForce Public License Version 1.1 (the "License"); you may not use this file except
 * in compliance with the License.
 * Software distributed under the License is distributed on an "AS IS" basis, WITHOUT WARRANTY OF ANY KIND, either express or implied.
 * See the License for the specific language governing rights and limitations under the License.
 * The Original Code is YetiForce.
 * The Initial Developer of the Original Code is YetiForce. Portions created by YetiForce are Copyright (C) www.yetiforce.com. 
 * All Rights Reserved.
 * *********************************************************************************************************************************** */

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
		$listQuery = $this->getBasicListQuery();

		$startIndex = $pagingModel->getStartIndex();
		$pageLimit = $pagingModel->getPageLimit();

		$orderBy = $this->getForSql('orderby');
		if (!empty($orderBy) && $orderBy === 'smownerid') {
			$fieldModel = \App\Modules\Base\Models\Field::getInstance('assigned_user_id', $moduleModel);
			if ($fieldModel->getFieldDataType() == 'owner') {
				$orderBy = 'COALESCE(' . \vtlib\Deprecated::getSqlForNameInDisplayFormat(['first_name' => 'vtiger_users.first_name', 'last_name' => 'vtiger_users.last_name'], 'Users') . ',vtiger_groups.groupname)';
			}
		}
		if (!empty($orderBy)) {
			if ($this->getForSql('sortorder') === 'ASC') {
				$listQuery->orderBy([$orderBy => SORT_ASC]);
			} else {
				$listQuery->orderBy([$orderBy => SORT_DESC]);
			}
		}
		$sourceModule = $this->get('sourceModule');
		if (!empty($sourceModule)) {
			$listQuery->where(['module' => \App\Module::getModuleId($sourceModule)]);
		}


		if ($module->isPagingSupported()) {
			$listQuery->limit($pageLimit + 1)->offset($startIndex);
		}

		$dataReader = $listQuery->createCommand()->query();
		$listViewRecordModels = [];
		while ($row = $dataReader->read()) {
			$record = new $recordModelClass();
			$record->setData($row);

			$recordModule = \vtlib\Functions::getModuleName($row['module']);
			$record->set('module', \App\Runtime\Vtiger_Language_Handler::translate($recordModule, $recordModule));

			if (method_exists($record, 'getModule') && method_exists($record, 'setModule')) {
				$moduleModel = \App\Modules\Settings\Base\Models\Module::getInstance($qualifiedModuleName);
				$record->setModule($moduleModel);
			}
			$listViewRecordModels[$record->getId()] = $record;
		}
		if ($module->isPagingSupported()) {
			$pagingModel->calculatePageRange($dataReader->count());
			if ($dataReader->count() > $pageLimit) {
				$pagingModel->set('nextPageExists', true);
			} else {
				$pagingModel->set('nextPageExists', false);
			}
		}
		return $listViewRecordModels;
	}
}
