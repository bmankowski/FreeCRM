<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * Strips the companion time-field name from the DT~time_start typeofdata encoding
 * on the two date_start fields in vtiger_activity (fieldids 233, 257).
 *
 * The companion relationship (uitype 6 DateTime field + a sibling time column) is
 * expressed by the uitype number alone; the ~time_start segment is not read by any
 * runtime PHP code. Stripping it makes typeofdata a pure type-code column for
 * datetime fields: DT~time_start → DT.
 *
 * Run via: yii migrate --migrationPath=migrations/Users/
 */

declare(strict_types=1);

use yii\db\Migration;

class m260526_000006_typeofdata_strip_dt_companion extends Migration
{
	private const TABLE = 'vtiger_field';

	public function safeUp(): void
	{
		$this->db->createCommand(
			"UPDATE " . self::TABLE . " SET typeofdata = 'DT' WHERE typeofdata LIKE 'DT~%'"
		)->execute();
	}

	public function safeDown(): void
	{
		$this->db->createCommand(
			"UPDATE " . self::TABLE . " SET typeofdata = 'DT~time_start'"
			. " WHERE typeofdata = 'DT' AND fieldname = 'date_start' AND tablename = 'vtiger_activity'"
		)->execute();
	}
}
