<?php
/**
 * FreeCRM - Rename picklist vtiger_zrodlo_aplikacji → vtiger_application_source.
 *
 * Run via: docker compose exec -T app php yii migrate --migrationPath=migrations/Users/ --interactive=0
 */

declare(strict_types=1);

use yii\db\Migration;

class m260609_000003_rename_zrodlo_aplikacji_picklist extends Migration
{
	private const TABLE_OLD = 'vtiger_zrodlo_aplikacji';
	private const TABLE_NEW = 'vtiger_application_source';

	public function safeUp(): void
	{
		if ($this->db->getTableSchema(self::TABLE_NEW, true) !== null) {
			echo "Picklist table " . self::TABLE_NEW . " already exists.\n";
			return;
		}
		if ($this->db->getTableSchema(self::TABLE_OLD, true) === null) {
			echo "Legacy picklist table " . self::TABLE_OLD . " not found — skipping.\n";
			return;
		}

		$this->renameTable(self::TABLE_OLD, self::TABLE_NEW);
		echo "Renamed table " . self::TABLE_OLD . " → " . self::TABLE_NEW . "\n";

		$this->renameColumn(self::TABLE_NEW, 'zrodlo_aplikacjiid', 'application_sourceid');
		$this->renameColumn(self::TABLE_NEW, 'zrodlo_aplikacji', 'application_source');
		echo "Renamed picklist columns to application_sourceid / application_source\n";

		$count = $this->db->createCommand(
			"UPDATE vtiger_picklist SET name = 'application_source' WHERE name = 'zrodlo_aplikacji'"
		)->execute();
		echo "Updated $count vtiger_picklist row(s)\n";
	}

	public function safeDown(): void
	{
		echo "This migration is not reversible automatically.\n";
	}
}
