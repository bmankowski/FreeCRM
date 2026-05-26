<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * Strips redundant LE~n length constraints from typeofdata (maximumlength is authoritative).
 * Also fixes the one drifted field where LE~255 disagrees with maximumlength=100.
 *
 * Run via: yii migrate --migrationPath=migrations/Users/
 */

declare(strict_types=1);

use yii\db\Migration;

class m260526_000002_typeofdata_strip_le extends Migration
{
	public function safeUp(): void
	{
		$this->db->createCommand(
			"UPDATE vtiger_field SET typeofdata = 'V~O'
			WHERE fieldname = 'cf_2610' AND typeofdata = 'V~O~LE~255'"
		)->execute();

		$this->db->createCommand(
			"UPDATE vtiger_field
			SET typeofdata = SUBSTRING_INDEX(typeofdata, '~LE~', 1)
			WHERE typeofdata LIKE '%~LE~%'"
		)->execute();
	}

	public function safeDown(): void
	{
		// LE~n was read by nothing — no functional rollback needed.
		// The data would need to be re-derived from maximumlength, which is lossy
		// for cf_2610 (we cannot know whether LE~255 or LE~100 was intended).
	}
}
