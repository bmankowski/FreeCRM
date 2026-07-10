<?php
/**
 * FreeCRM - Backfill vtiger_field defaultvalue for Users preference columns left NULL on create.
 *
 * Run via: docker compose exec -T app php yii migrate --migrationPath=migrations/Users/ --interactive=0
 */

declare(strict_types=1);

use yii\db\Migration;

class m260710_000001_users_readonly_field_defaults extends Migration
{
	public $transaction = false;

	public function safeUp(): void
	{
		$usersTabId = (int) $this->db->createCommand(
			"SELECT tabid FROM vtiger_tab WHERE name = 'Users'"
		)->queryScalar();

		$fields = $this->db->createCommand(
			'SELECT columnname, defaultvalue FROM vtiger_field
			 WHERE tabid = :tabid AND defaultvalue IS NOT NULL AND defaultvalue != \'\''
		)->bindValue(':tabid', $usersTabId)->queryAll();

		$totalUpdated = 0;
		foreach ($fields as $field) {
			$column = $field['columnname'];
			if (!preg_match('/^[a-z0-9_]+$/', $column)) {
				throw new \RuntimeException("Invalid Users column name: {$column}");
			}
			$dataType = $this->db->createCommand(
				'SELECT DATA_TYPE FROM information_schema.COLUMNS
				 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = \'vtiger_users\' AND COLUMN_NAME = :column'
			)->bindValue(':column', $column)->queryScalar();
			$isNumeric = in_array($dataType, ['int', 'tinyint', 'smallint', 'mediumint', 'bigint', 'decimal', 'float', 'double'], true);
			$nullCondition = $isNumeric
				? "`{$column}` IS NULL"
				: "(`{$column}` IS NULL OR `{$column}` = '')";
			$updated = $this->db->createCommand(
				"UPDATE vtiger_users SET `{$column}` = :defaultValue
				 WHERE deleted = 0 AND {$nullCondition}"
			)->bindValue(':defaultValue', $field['defaultvalue'])->execute();
			if ($updated > 0) {
				echo sprintf("Set %s=%s on %d user(s).\n", $column, $field['defaultvalue'], $updated);
				$totalUpdated += $updated;
			}
		}

		echo sprintf("Backfilled %d column value(s) across Users rows.\n", $totalUpdated);

		\App\Utils\VtlibUtils::recreateUserPrivilegeFiles();
		echo "Regenerated user_privileges_*.php from DB.\n";
	}

	public function safeDown(): void
	{
		echo "This migration is not reversible automatically.\n";
	}
}
