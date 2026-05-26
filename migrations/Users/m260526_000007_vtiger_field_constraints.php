<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * Creates vtiger_field_constraints and migrates all cross-field comparison
 * constraints out of vtiger_field.typeofdata (OTH segments) into the new table.
 *
 * After this migration typeofdata contains only a single type-code token (V, D, T,
 * DT, I, N, NN, C, E, P, M) with no trailing segments — fully First Normal Form.
 *
 * Row coverage:
 *   - 16 direct GE/G constraints from existing typeofdata OTH rows
 *   -  6 LE inverse constraints from the hardcoded getValidator() switch
 *   -  1 additional LE (end_period <= duedate) from getValidator()
 *   -  3 bug-fix rows previously missing from both typeofdata and getValidator()
 *        (dateinservice, time_start in osstimecontrol and reservations)
 *
 * Run via: yii migrate --migrationPath=migrations/Users/
 */

declare(strict_types=1);

use yii\db\Migration;

class m260526_000007_vtiger_field_constraints extends Migration
{
	private const CONSTRAINTS = 'vtiger_field_constraints';
	private const FIELD       = 'vtiger_field';

	public function safeUp(): void
	{
		$this->db->createCommand("
			CREATE TABLE IF NOT EXISTS `" . self::CONSTRAINTS . "` (
				`id`            INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
				`fieldid`       INT(10) UNSIGNED NOT NULL,
				`operator`      ENUM('GE','G','LE','L') NOT NULL COMMENT 'GE>=  G>  LE<=  L<',
				`ref_fieldname` VARCHAR(50) NOT NULL,
				PRIMARY KEY (`id`),
				KEY `idx_fieldid` (`fieldid`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8
		")->execute();

		// ── Direct GE/G from typeofdata OTH rows ─────────────────────────────
		$rows = [
			// (fieldid, operator, ref_fieldname)
			[95,   'GE', 'support_start_date'],   // support_end_date >= support_start_date
			[180,  'GE', 'sales_start_date'],      // sales_end_date (products) >= sales_start_date
			[182,  'GE', 'start_date'],             // expiry_date (products) >= start_date
			[236,  'GE', 'date_start'],             // due_date (activity Events) >= date_start
			[259,  'GE', 'date_start'],             // due_date (activity Calendar) >= date_start
			[564,  'GE', 'sales_start_date'],      // sales_end_date (service) >= sales_start_date
			[566,  'GE', 'start_date'],             // expiry_date (service) >= start_date
			[582,  'GE', 'dateinservice'],          // datesold >= dateinservice (corrected)
			[624,  'GE', 'startdate'],              // enddate (projecttask) >= startdate
			[631,  'GE', 'startdate'],              // targetenddate (project) >= startdate
			[632,  'GE', 'startdate'],              // actualenddate (project) >= startdate
			[786,  'GE', 'time_start'],             // time_end (osstimecontrol) >= time_start
			[787,  'GE', 'date_start'],             // due_date (osstimecontrol) >= date_start
			[1722, 'GE', 'time_start'],             // time_end (reservations) >= time_start
			[1723, 'GE', 'date_start'],             // due_date (reservations) >= date_start
			[1872, 'G',  'start_period'],           // end_period > start_period (strict)

			// ── LE inverses from hardcoded getValidator() ─────────────────────
			[94,   'LE', 'support_end_date'],       // support_start_date <= support_end_date
			[179,  'LE', 'sales_end_date'],         // sales_start_date (products) <= sales_end_date
			[563,  'LE', 'sales_end_date'],         // sales_start_date (service) <= sales_end_date
			[623,  'LE', 'enddate'],                // startdate (projecttask) <= enddate
			[630,  'LE', 'targetenddate'],          // startdate (project) <= targetenddate
			[1871, 'LE', 'end_period'],             // start_period <= end_period

			// ── Additional constraint from getValidator() ─────────────────────
			[1872, 'LE', 'duedate'],                // end_period <= duedate

			// ── Bug fixes: were in typeofdata but missing from getValidator() ──
			[583,  'LE', 'datesold'],               // dateinservice <= datesold
			[785,  'LE', 'time_end'],               // time_start (osstimecontrol) <= time_end
			[1721, 'LE', 'time_end'],               // time_start (reservations) <= time_end
		];

		foreach ($rows as [$fieldid, $operator, $ref]) {
			$this->insert(self::CONSTRAINTS, [
				'fieldid'       => $fieldid,
				'operator'      => $operator,
				'ref_fieldname' => $ref,
			]);
		}

		// ── Strip OTH segments from typeofdata ────────────────────────────────
		// Pattern: <type_code>~OTH~... → <type_code>
		$this->db->createCommand(
			"UPDATE " . self::FIELD . " SET typeofdata = REGEXP_SUBSTR(typeofdata, '^[A-Z]+')"
			. " WHERE typeofdata REGEXP '^[A-Z]+~OTH~'"
		)->execute();
	}

	public function safeDown(): void
	{
		$this->dropTable(self::CONSTRAINTS);

		// Restore original typeofdata values for the 16 OTH rows
		$restore = [
			[95,   "D~OTH~GE~support_start_date~Support Start Date"],
			[180,  "D~OTH~GE~sales_start_date~Sales Start Date"],
			[182,  "D~OTH~GE~start_date~Start Date"],
			[236,  "D~OTH~GE~date_start~Start Date & Time"],
			[259,  "D~OTH~GE~date_start~Start Date & Time"],
			[564,  "D~OTH~GE~sales_start_date~Sales Start Date"],
			[566,  "D~OTH~GE~start_date~Start Date"],
			[582,  "D~OTH~GE~dateinservice~Date in Service"],
			[624,  "D~OTH~GE~startdate~Start Date"],
			[631,  "D~OTH~GE~startdate~Start Date"],
			[632,  "D~OTH~GE~startdate~Start Date"],
			[786,  "T~OTH~GE~time_start~Time Start"],
			[787,  "D~OTH~GE~date_start~Start Date"],
			[1722, "T~OTH~GE~time_start~LBL_TIME_START"],
			[1723, "D~OTH~GE~date_start~LBL_START_DATE"],
			[1872, "D~OTH~G~start_period~LBL_START_PERIOD"],
		];
		foreach ($restore as [$fieldid, $value]) {
			$this->db->createCommand(
				"UPDATE " . self::FIELD . " SET typeofdata = :v WHERE fieldid = :id",
				[':v' => $value, ':id' => $fieldid]
			)->execute();
		}
	}
}
