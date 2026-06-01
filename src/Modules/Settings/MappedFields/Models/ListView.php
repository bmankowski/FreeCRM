<?php

namespace App\Modules\Settings\MappedFields\Models;



/**
 * List View Model Class for MappedFields Settings
 * @package YetiForce.Model
 * @license licenses/License.html
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */
class ListView extends \App\Modules\Settings\Base\Models\ListView
{

	/**
	 * Function to get the list view entries
	 * @param \App\Modules\Base\Models\Paging $pagingModel
	 * @return array - Associative array of record id mapped to \App\Modules\Base\Models\Record instance.
	 */
	public function getListViewEntries($pagingModel)
	{
		$module = $this->getModule();
		$parentModuleName = $module->getParentName();
		if (!empty($parentModuleName)) {
			$qualifiedModuleName = $parentModuleName . ':' . $module->getName();
		}
		$recordModelClass = \App\Core\Loader::getComponentClassName('Model', 'Record', $qualifiedModuleName);
		$listFields = $module->getQueryableListFields();
		$listFields[] = $module->baseIndex;
		$query = (new \App\Db\Query())->select($listFields)
			->from($module->baseTable);
		$sourceModule = $this->get('sourceModule');
		if (!empty($sourceModule)) {
			$query->where(['tabid' => $sourceModule]);
		}
		$startIndex = $pagingModel->getStartIndex();
		$pageLimit = $pagingModel->getPageLimit();
		$orderBy = $this->getForSql('orderby');
		if (!empty($orderBy)) {
			$query->orderBy(sprintf('%s %s ', $orderBy, $this->getForSql('sortorder')));
		}
		$query->limit($pageLimit + 1)->offset($startIndex);
		$dataReader = $query->createCommand()->query();
		$listViewRecordModels = [];
		while ($row = $dataReader->read()) {
			$recordModel = new $recordModelClass();
			$moduleName = \App\Utils\ModuleUtils::getModuleName($row['tabid']);
			$relModuleName = \App\Utils\ModuleUtils::getModuleName($row['reltabid']);
			$row['tabid'] = \App\Runtime\Vtiger_Language_Handler::translate($moduleName, $moduleName);
			$row['reltabid'] = \App\Runtime\Vtiger_Language_Handler::translate($relModuleName, $relModuleName);
			$recordModel->setData($row);
			$listViewRecordModels[$recordModel->getId()] = $recordModel;
		}
		$pagingModel->calculatePageRange($dataReader->count());
		if ($dataReader->count() > $pageLimit) {
			array_pop($listViewRecordModels);
			$pagingModel->set('nextPageExists', true);
		} else {
			$pagingModel->set('nextPageExists', false);
		}
		return $listViewRecordModels;
	}
	/*
	 * Function which will get the list view count
	 * @return - number of records
	 */

	public function getListViewCount()
	{
		$module = $this->getModule();
		$db = \App\Db\Db::getInstance('admin');
		$query = (new \App\Db\Query())->from($module->baseTable);
		$sourceModule = $this->get('sourceModule');
		if ($sourceModule) {
			$query->where(['tabid' => $sourceModule]);
		}
		return $query->count('*', $db);
	}
}
