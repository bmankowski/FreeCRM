<?php
/**
 * FreeCRM - Normalize vtiger_customview.sort to comma format; seed Candidates All sort.
 *
 * Run via: docker compose exec -T app php yii migrate --migrationPath=migrations/Users/ --interactive=0
 */

declare(strict_types=1);

use yii\db\Migration;

class m260616_000003_customview_sort_comma_format extends Migration
{
	/** @var array<int, string> */
	private const SORT_FIXES = [
		399 => 'createdtime,DESC',
		353 => 'createdtime,DESC',
		354 => 'createdtime,DESC',
	];

	public function safeUp(): void
	{
		$rows = (new \App\Db\Query())
			->select(['cvid', 'sort'])
			->from('vtiger_customview')
			->where(['like', 'sort', '{%', false])
			->all();
		foreach ($rows as $row) {
			$decoded = json_decode($row['sort'], true);
			if (!is_array($decoded) || count($decoded) !== 1) {
				echo "WARN: skip cvid {$row['cvid']} — unexpected JSON sort\n";
				continue;
			}
			$field = array_key_first($decoded);
			$order = strtoupper((string) $decoded[$field]) === 'DESC' ? 'DESC' : 'ASC';
			$sort = $field . ',' . $order;
			$this->db->createCommand()->update(
				'vtiger_customview',
				['sort' => $sort],
				['cvid' => $row['cvid']]
			)->execute();
			echo "Converted JSON sort to $sort for cvid {$row['cvid']}\n";
		}

		foreach (self::SORT_FIXES as $cvid => $sort) {
			$count = $this->db->createCommand()->update('vtiger_customview', ['sort' => $sort], ['cvid' => $cvid])->execute();
			echo "Set sort=$sort on $count row(s) for cvid $cvid\n";
		}

		\App\Cache\Cache::clear();
	}

	public function safeDown(): void
	{
		echo "This migration is not reversible automatically.\n";
		echo "Manual revert: UPDATE vtiger_customview SET sort = '{\"modifiedtime\":\"DESC\"}' WHERE cvid = 399;\n";
		echo "              UPDATE vtiger_customview SET sort = '{\"createdtime\":\"DESC\"}' WHERE cvid IN (353, 354);\n";
	}
}
