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

namespace App\Modules\Settings\MailAccount\Models;

class ListView extends \App\Modules\Settings\Base\Models\ListView
{
	public function getListViewEntries($pagingModel)
	{
		$moduleModel = $this->getModule();
		$recordModelClass = \App\Core\Loader::getComponentClassName('Model', 'Record', 'Settings:MailAccount');
		$listViewRecordModels = [];
		foreach (\App\Modules\Mail\Models\Account::listAllForAdmin() as $row) {
			$record = new $recordModelClass();
			$record->setData($row);
			if (method_exists($record, 'setModule')) {
				$record->setModule($moduleModel);
			}
			$listViewRecordModels[$record->getId()] = $record;
		}
		if ($moduleModel->isPagingSupported()) {
			$pagingModel->calculatePageRange(count($listViewRecordModels));
		}
		return $listViewRecordModels;
	}

	public function getListViewCount()
	{
		return count(\App\Modules\Mail\Models\Account::listAllForAdmin());
	}

	public function getBasicLinks(): array
	{
		$moduleModel = $this->getModule();

		return [
			[
				'linktype' => 'LISTVIEWBASIC',
				'linklabel' => 'LBL_LOGS',
				'linkurl' => 'index.php?module=MailAccount&parent=Settings&view=Logs',
				'linkclass' => 'btn-default',
				'showLabel' => 1,
			],
			[
				'linktype' => 'LISTVIEWBASIC',
				'linklabel' => 'LBL_ADD_SHARED_ACCOUNT',
				'linkurl' => $moduleModel->getCreateRecordUrl(),
				'linkclass' => 'btn-success addButton',
				'linkicon' => 'glyphicon glyphicon-plus',
				'showLabel' => 1,
			],
		];
	}
}
