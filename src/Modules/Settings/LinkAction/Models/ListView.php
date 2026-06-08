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

namespace App\Modules\Settings\LinkAction\Models;

class ListView extends \App\Modules\Settings\Base\Models\ListView
{
	public function getBasicListQuery()
	{
		$module = $this->getModule();
		$baseTable = $module->baseTable;
		$query = (new \App\Db\Query())
			->select([
				"$baseTable.*",
				'msg.subject AS send_subject',
				'msg.id AS mail_message_row_id',
				'msg.send_status AS mail_send_status',
			])
			->from(["$baseTable" => $baseTable])
			->leftJoin(['msg' => 'u_yf_mail_messages'], "msg.id = $baseTable.mail_message_id");

		$searchKey = $this->get('search_key');
		$searchValue = $this->get('search_value');
		if (!empty($searchKey) && !empty($searchValue)) {
			$query->where(["$baseTable.$searchKey" => $searchValue]);
		}

		$orderBy = $this->getForSql('orderby');
		if (empty($orderBy)) {
			$query->orderBy(["$baseTable.clicked_at" => SORT_DESC, "$baseTable.id" => SORT_DESC]);
		}

		return $query;
	}

	public function getListViewLinks(): array
	{
		return [
			'LISTVIEWBASIC' => [],
			'LISTVIEW' => [],
		];
	}

	public function getListViewCount()
	{
		$query = $this->getBasicListQuery();
		$query->orderBy([]);
		return $query->count();
	}

	/**
	 * @return string[]
	 */
	public static function getModuleFilterOptions(): array
	{
		$modules = (new \App\Db\Query())
			->select('module')
			->distinct()
			->from('u_yf_link_action_log')
			->orderBy(['module' => SORT_ASC])
			->column();

		$options = [];
		foreach ($modules as $moduleName) {
			$options[(string) $moduleName] = \App\Runtime\Vtiger_Language_Handler::translate(
				(string) $moduleName,
				(string) $moduleName
			);
		}
		return $options;
	}
}
