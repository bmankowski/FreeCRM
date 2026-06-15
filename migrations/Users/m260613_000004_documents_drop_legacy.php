<?php
/**
 * FreeCRM - Documents storage rebuild (destructive phase).
 *
 * Run via: docker compose exec -T app php yii migrate --migrationPath=migrations/Users/ --interactive=0
 */

declare(strict_types=1);

use yii\db\Migration;

class m260613_000004_documents_drop_legacy extends Migration
{
	public $transaction = false;

	private const DROP_COLUMNS = [
		'filename',
		'filetype',
		'filesize',
		'filelocationtype',
		'filestatus',
		'filedownloadcount',
		'fileversion',
		'ossdc_status',
	];

	private const DROP_TABLES = [
		'vtiger_seattachmentsrel',
		'vtiger_salesmanattachmentsrel',
		'vtiger_attachments',
		'vtiger_notescf',
	];

	public function safeUp(): void
	{
		$this->dropLegacyNoteColumns();
		$this->dropLegacyTables();
	}

	public function safeDown(): void
	{
		echo "m260613_000004_documents_drop_legacy: safeDown not supported — restore DB backup.\n";
	}

	private function dropLegacyNoteColumns(): void
	{
		$schema = $this->db->getSchema()->getTableSchema('vtiger_notes', true);
		if ($schema === null) {
			return;
		}
		foreach (self::DROP_COLUMNS as $column) {
			if (isset($schema->columns[$column])) {
				$this->dropColumn('vtiger_notes', $column);
			}
		}
	}

	private function dropLegacyTables(): void
	{
		$this->db->createCommand('SET FOREIGN_KEY_CHECKS=0')->execute();
		foreach (self::DROP_TABLES as $table) {
			if ($this->db->getSchema()->getTableSchema($table, true) !== null) {
				$this->dropTable($table);
			}
		}
		$this->db->createCommand('SET FOREIGN_KEY_CHECKS=1')->execute();
	}
}
