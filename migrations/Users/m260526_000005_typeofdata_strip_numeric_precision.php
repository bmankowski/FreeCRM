<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * Strips the ~2~2 precision encoding from typeofdata on the three N-type percent
 * fields (progress, discount, probability). The values duplicate what the DB column
 * type already encodes and are not read by any runtime PHP code.
 *
 * Also fixes a pre-existing self-referential OTH constraint on vtiger_assets:
 *   - dateinservice: removes meaningless "dateinservice >= dateinservice" constraint
 *   - datesold: corrects "datesold >= datesold" → "datesold >= dateinservice"
 *
 * Run via: yii migrate --migrationPath=migrations/Users/
 */

declare(strict_types=1);

use yii\db\Migration;

class m260526_000005_typeofdata_strip_numeric_precision extends Migration
{
	private const TABLE = 'vtiger_field';

	public function safeUp(): void
	{
		$this->db->createCommand(
			"UPDATE " . self::TABLE . " SET typeofdata = 'N' WHERE typeofdata = 'N~2~2'"
		)->execute();

		$this->db->createCommand(
			"UPDATE " . self::TABLE . " SET typeofdata = 'D'"
			. " WHERE fieldname = 'dateinservice' AND tablename = 'vtiger_assets'"
		)->execute();

		$this->db->createCommand(
			"UPDATE " . self::TABLE . " SET typeofdata = 'D~OTH~GE~dateinservice~Date in Service'"
			. " WHERE fieldname = 'datesold' AND tablename = 'vtiger_assets'"
		)->execute();
	}

	public function safeDown(): void
	{
		$this->db->createCommand(
			"UPDATE " . self::TABLE . " SET typeofdata = 'N~2~2'"
			. " WHERE typeofdata = 'N' AND fieldname IN ('progress','discount','probability')"
		)->execute();

		$this->db->createCommand(
			"UPDATE " . self::TABLE . " SET typeofdata = 'D~OTH~GE~dateinservice~Date in Service'"
			. " WHERE fieldname = 'dateinservice' AND tablename = 'vtiger_assets'"
		)->execute();

		$this->db->createCommand(
			"UPDATE " . self::TABLE . " SET typeofdata = 'D~OTH~GE~datesold~Date Sold'"
			. " WHERE fieldname = 'datesold' AND tablename = 'vtiger_assets'"
		)->execute();
	}
}
