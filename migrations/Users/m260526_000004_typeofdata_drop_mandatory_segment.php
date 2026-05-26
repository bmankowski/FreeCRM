<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * Removes the M/O mandatory flag (segment 2) from typeofdata in vtiger_field.
 * The mandatory column is now the sole authority for field required status.
 * typeofdata_old retains the pre-change snapshot.
 *
 * Run via: yii migrate --migrationPath=migrations/Users/
 */

declare(strict_types=1);

use yii\db\Migration;

class m260526_000004_typeofdata_drop_mandatory_segment extends Migration
{
	public function safeUp(): void
	{
		// Strip segment 2 (M/O):
		//   "V~M"              → "V"
		//   "V~O"              → "V"
		//   "D~M~OTH~GE~..."   → "D~OTH~GE~..."
		//   "N~O~2~2"          → "N~2~2"
		//   "DT~M~time_start"  → "DT~time_start"
		$this->db->createCommand("
			UPDATE vtiger_field
			SET typeofdata = CONCAT(
				SUBSTRING_INDEX(typeofdata, '~', 1),
				IF(
					LENGTH(typeofdata) - LENGTH(REPLACE(typeofdata, '~', '')) >= 2,
					SUBSTRING(typeofdata, LOCATE('~', typeofdata, LOCATE('~', typeofdata) + 1)),
					''
				)
			)
			WHERE typeofdata LIKE '%~%'
		")->execute();
	}

	public function safeDown(): void
	{
		// Restore from the snapshot created by m260526_000003.
		// Only restores rows where typeofdata_old differs.
		$schema = $this->db->getSchema()->getTableSchema('vtiger_field', true);
		if ($schema !== null && isset($schema->columns['typeofdata_old'])) {
			$this->db->createCommand("
				UPDATE vtiger_field
				SET typeofdata = typeofdata_old
				WHERE typeofdata_old IS NOT NULL
				  AND typeofdata_old != typeofdata
			")->execute();
		}
	}
}
