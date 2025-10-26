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
		if (!empty($parentModuleName)) {
			$qualifiedModuleName = $parentModuleName . ':' . $module->getName();
		}
		$recordModelClass = \App\Loader::getComponentClassName('Model', 'Record', $qualifiedModuleName);
		$listFields = array_keys($module->listFields);
		$listFields [] = $module->baseIndex;
		$query = (new \App\Db\Query())->select($listFields)
			->from($module->baseTable);
		$searchParams = $this->get('searchParams');
		if(!empty($searchParams)){
			foreach ($searchParams as $key => $value) {
				if('' !== $value['value']){
					$query->andWhere([$key => $value['value']]);
				}
			}
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
			$moduleName = \App\Module::getModuleName($row['tabid']);
			$relModuleName = \App\Module::getModuleName($row['reltabid']);
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
	
	public function getBasicLinks(){
		return [];
	}
}
