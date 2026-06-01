<?php

namespace App\Modules\Settings\Mail\Models;



/**
 * List View Model Class for Mail Settings
 * @package YetiForce.Settings.Record
 * @license licenses/License.html
 * @author Adrian Koń <a.kon@yetiforce.com>
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
		$qualifiedModuleName = $module->getName();
		if (!empty($parentModuleName)) {
			$qualifiedModuleName = $parentModuleName . ':' . $qualifiedModuleName;
		}
		$recordModelClass = \App\Core\Loader::getComponentClassName('Model', 'Record', $qualifiedModuleName);
		$listFields = $module->getQueryableListFields();
		if (!in_array($module->baseIndex, $listFields, true)) {
			$listFields[] = $module->baseIndex;
		}
		$query = (new \App\Db\Query())->select($listFields)
			->from($module->baseTable);
		$searchParams = $this->get('searchParams');
		if (!empty($searchParams)) {
			foreach ($searchParams as $key => $value) {
				if ('' !== $value['value']) {
					$query->andWhere([$key => $value['value']]);
				}
			}
		}

		$startIndex = $pagingModel->getStartIndex();
		$pageLimit = $pagingModel->getPageLimit();
		$orderBy = $this->getForSql('orderby');
		if (!empty($orderBy) && !$module->isVirtualListField($orderBy)) {
			$query->orderBy(sprintf('%s %s ', $orderBy, $this->getForSql('sortorder')));
		}
		$query->limit($pageLimit + 1)->offset($startIndex);
		$dataReader = $query->createCommand()->query();
		$listViewRecordModels = [];
		while ($row = $dataReader->read()) {
			$recordModel = new $recordModelClass();
			$recordModel->setData($row);
			if (method_exists($recordModel, 'setModule')) {
				$recordModel->setModule($module);
			}
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
	
	public function getBasicLinks(){
		return [];
	}
}
