<?php
/**
 * FreeCRM - Widen relation workflow status filter columns for multi-select JSON.
 *
 * Run via: yii migrate --migrationPath=migrations/Users/
 */

declare(strict_types=1);

use yii\db\Migration;

class m260608_000004_workflow_relation_status_multiselect extends Migration
{
	public function safeUp(): void
	{
		$table = 'com_vtiger_workflow_relation_triggers';
		if ($this->db->schema->getTableSchema($table, true) === null) {
			return;
		}
		$this->alterColumn($table, 'source_value', $this->text());
		$this->alterColumn($table, 'destination_value', $this->text()->notNull());
	}

	public function safeDown(): void
	{
		$table = 'com_vtiger_workflow_relation_triggers';
		if ($this->db->schema->getTableSchema($table, true) === null) {
			return;
		}
		$this->alterColumn($table, 'source_value', $this->string(255)->null());
		$this->alterColumn($table, 'destination_value', $this->string(255)->notNull());
	}
}
