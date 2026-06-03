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

	private function applySearchParamsToQuery(\App\Db\Query $query): void
	{
		$searchParams = $this->get('searchParams');
		if (empty($searchParams)) {
			return;
		}
		foreach ($searchParams as $key => $value) {
			if (is_array($value) && '' !== ($value['value'] ?? '')) {
				$query->andWhere([$key => $value['value']]);
			}
		}
	}

	/**
	 * @return int[]
	 */
	public function getRecordIds(?array $excludedIds = null): array
	{
		$module = $this->getModule();
		$query = (new \App\Db\Query())->select([$module->baseIndex])
			->from($module->baseTable);
		$this->applySearchParamsToQuery($query);
		if (!empty($excludedIds)) {
			$query->andWhere(['not in', $module->baseIndex, $excludedIds]);
		}
		return $query->column(\App\Db\Db::getInstance('admin'));
	}

	/**
	 * @return int[]
	 */
	public static function getRecordIdsFromRequest(\App\Http\Vtiger_Request $request): array
	{
		$selectedIds = $request->get('selected_ids');
		$excludedIds = $request->get('excluded_ids') ?: [];

		if (is_string($selectedIds)) {
			$decoded = json_decode($selectedIds, true);
			if ($decoded !== null) {
				$selectedIds = $decoded;
			}
		}
		if (is_string($excludedIds)) {
			$excludedIds = json_decode($excludedIds, true) ?: [];
		}

		if ($selectedIds === 'all' || $selectedIds === '"all"') {
			$listViewModel = self::getInstance($request->getModule(false));
			$searchParams = $request->get('searchParams');
			if (!empty($searchParams)) {
				$listViewModel->set('searchParams', $searchParams);
			}
			return $listViewModel->getRecordIds($excludedIds);
		}

		if (!empty($selectedIds) && is_array($selectedIds)) {
			return array_values(array_diff($selectedIds, $excludedIds));
		}

		return [];
	}

	public function getListViewCount()
	{
		$query = $this->getBasicListQuery();
		$this->applySearchParamsToQuery($query);
		return $query->count('*', \App\Db\Db::getInstance('admin'));
	}

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
		$this->applySearchParamsToQuery($query);

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
	
	public function getBasicLinks(): array
	{
		return [
			[
				'linktype' => 'LISTVIEWBASIC',
				'linklabel' => 'LBL_MASS_DELETE',
				'linkurl' => '',
				'linkclass' => 'btn-danger massDelete',
				'linkicon' => 'glyphicon glyphicon-trash',
				'showLabel' => 1,
			],
		];
	}
}
