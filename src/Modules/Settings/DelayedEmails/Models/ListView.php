<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * @project FreeCRM
 * @author bmankowski@gmail.com
 * @copyright (c) FreeCRM
 * @license FreeCRM Public License 1.1
 */

declare(strict_types=1);

namespace App\Modules\Settings\DelayedEmails\Models;

class ListView extends \App\Modules\Settings\Base\Models\ListView
{
	public function getBasicListQuery()
	{
		return (new \App\Db\Query())
			->from('s_#__delayed_email_queue')
			->orderBy(['send_after' => SORT_ASC]);
	}

	public function getListViewEntries($pagingModel)
	{
		$moduleModel = $this->getModule();
		$qualifiedModuleName = 'Settings:DelayedEmails';
		$recordModelClass = \App\Core\Loader::getComponentClassName('Model', 'Record', $qualifiedModuleName);
		$listQuery = $this->getBasicListQuery();

		$orderBy = $this->getForSql('orderby');
		if (!empty($orderBy) && $orderBy !== 'recipient') {
			if ($this->getForSql('sortorder') === 'DESC') {
				$listQuery->orderBy([$orderBy => SORT_DESC]);
			} else {
				$listQuery->orderBy([$orderBy => SORT_ASC]);
			}
		}

		$dataReader = $listQuery->createCommand()->query();
		$listViewRecordModels = [];
		while ($row = $dataReader->read()) {
			$record = new $recordModelClass();
			$record->setData($row);
			if (method_exists($record, 'setModule')) {
				$record->setModule($moduleModel);
			}
			$listViewRecordModels[$record->getId()] = $record;
		}
		if ($moduleModel->isPagingSupported()) {
			$pagingModel->calculatePageRange($dataReader->count());
		}
		return $listViewRecordModels;
	}

	public function getBasicLinks(): array
	{
		return [];
	}
}
