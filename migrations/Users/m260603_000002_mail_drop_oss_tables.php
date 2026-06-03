<?php
/**
 * FreeCRM - Hide legacy mail modules (tabid 48/53/54) and drop legacy mail DB tables.
 *
 * Run via: docker compose exec -T app php yii migrate --migrationPath=migrations/Users/ --interactive=0
 */

declare(strict_types=1);

use yii\db\Migration;

class m260603_000002_mail_drop_oss_tables extends Migration
{
	private const LEGACY_MAIL_TAB_IDS = [48, 53, 54];

	private const LEGACY_SETTINGS_FIELD_IDS = [44, 45, 46, 78, 94];

	private const LEGACY_CRON_IDS = [10, 11, 12];

	/** Tables dropped by prefix (legacy webmail stack). */
	private const DROP_PREFIXES = ['roundcube_', 'vtiger_ossmail'];

	/** Extra legacy tables without a common prefix. */
	private const DROP_EXTRA = ['vtiger_ossmails_logs', 'yetiforce_mail_quantities', 'u_yf_mail_autologin', 'u_yf_mail_compose_data'];

	public function safeUp(): void
	{
		$this->hideLegacyMailTabs();
		$this->deleteLegacyCronTasks();
		$this->deleteLegacySettingsFields();
		$this->deleteLegacyRelatedLists();
		$this->dropLegacyTables();
	}

	public function safeDown(): void
	{
		echo "m260603_000002_mail_drop_oss_tables: safeDown not supported — restore DB backup.\n";
	}

	private function hideLegacyMailTabs(): void
	{
		$this->update('vtiger_tab', ['presence' => 1], ['tabid' => self::LEGACY_MAIL_TAB_IDS]);
	}

	private function deleteLegacyCronTasks(): void
	{
		$this->db->createCommand()->delete('vtiger_cron_task', ['id' => self::LEGACY_CRON_IDS])->execute();
	}

	private function deleteLegacySettingsFields(): void
	{
		$this->db->createCommand()->delete('vtiger_settings_field', ['fieldid' => self::LEGACY_SETTINGS_FIELD_IDS])->execute();
	}

	private function deleteLegacyRelatedLists(): void
	{
		$this->db->createCommand()->delete('vtiger_relatedlists', [
			'or',
			['related_tabid' => self::LEGACY_MAIL_TAB_IDS],
			['tabid' => self::LEGACY_MAIL_TAB_IDS],
		])->execute();
	}

	private function dropLegacyTables(): void
	{
		$dbName = $this->db->createCommand('SELECT DATABASE()')->queryScalar();
		$tables = $this->db->createCommand(
			'SELECT table_name FROM information_schema.tables WHERE table_schema = :db',
			[':db' => $dbName]
		)->queryColumn();

		$toDrop = [];
		foreach ($tables as $table) {
			foreach (self::DROP_PREFIXES as $prefix) {
				if (str_starts_with($table, $prefix)) {
					$toDrop[] = $table;
					break;
				}
			}
		}
		foreach (self::DROP_EXTRA as $table) {
			if (in_array($table, $tables, true)) {
				$toDrop[] = $table;
			}
		}
		$toDrop = array_unique($toDrop);

		$this->db->createCommand('SET FOREIGN_KEY_CHECKS=0')->execute();
		foreach ($toDrop as $table) {
			$this->dropTable($table);
		}
		$this->db->createCommand('SET FOREIGN_KEY_CHECKS=1')->execute();
	}
}
