<?php
/**
 * FreeCRM - Drop duplicate RecruitmentApplication "All" custom view (cvid 529 → keep 528).
 *
 * Run via: docker compose exec -T app php yii migrate --migrationPath=migrations/Users/ --interactive=0
 */

declare(strict_types=1);

use yii\db\Migration;
use yii\db\Query;

class m260721_000002_drop_duplicate_recruitmentapplication_all_cv extends Migration
{
	private const KEEP_CVID = 528;
	private const DROP_CVID = 529;
	private const MODULE = 'RecruitmentApplication';

	/** @var list<string> */
	private const CHILD_TABLES = [
		'vtiger_cvcolumnlist',
		'vtiger_cvadvfilter',
		'vtiger_cvadvfilter_grouping',
		'vtiger_cvstdfilter',
		'vtiger_customaction',
		'u_yf_cv_condition_group',
		'u_yf_featured_filter',
	];

	public function safeUp(): void
	{
		if (!$this->isDuplicateAllView(self::DROP_CVID)) {
			return;
		}

		$this->retargetReferences(self::DROP_CVID, self::KEEP_CVID);
		$this->deleteCustomView(self::DROP_CVID);
	}

	public function safeDown(): void
	{
		// Duplicate filter was junk data; no restore.
	}

	private function isDuplicateAllView(int $cvid): bool
	{
		$row = (new Query())
			->from('vtiger_customview')
			->select(['cvid', 'viewname', 'entitytype'])
			->where(['cvid' => $cvid])
			->one($this->db);

		return is_array($row)
			&& (string) $row['viewname'] === 'All'
			&& (string) $row['entitytype'] === self::MODULE;
	}

	private function retargetReferences(int $from, int $to): void
	{
		if ($this->db->getTableSchema('vtiger_user_module_preferences', true) !== null) {
			$this->update('vtiger_user_module_preferences', ['default_cvid' => $to], ['default_cvid' => $from]);
		}
		if ($this->db->getTableSchema('vtiger_homemodule', true) !== null) {
			$this->update('vtiger_homemodule', ['customviewid' => $to], ['customviewid' => $from]);
		}
		if ($this->db->getTableSchema('u_yf_featured_filter', true) !== null) {
			$this->update('u_yf_featured_filter', ['cvid' => $to], ['cvid' => $from]);
		}
	}

	private function deleteCustomView(int $cvid): void
	{
		foreach (self::CHILD_TABLES as $table) {
			if ($this->db->getTableSchema($table, true) === null) {
				continue;
			}
			$this->delete($table, ['cvid' => $cvid]);
		}
		$this->delete('vtiger_customview', ['cvid' => $cvid]);
	}
}
