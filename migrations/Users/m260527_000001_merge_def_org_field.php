<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * Merges vtiger_def_org_field.visible into vtiger_field.org_visible and drops the legacy table.
 *
 * Run via: yii migrate --migrationPath=migrations/Users/
 */

declare(strict_types=1);

use yii\db\Migration;
use yii\db\Query;

class m260527_000001_merge_def_org_field extends Migration
{
	private const FIELD_TABLE = 'vtiger_field';
	private const LEGACY_TABLE = 'vtiger_def_org_field';

	public function safeUp(): void
	{
		$schema = $this->db->getSchema()->getTableSchema(self::FIELD_TABLE, true);
		if ($schema === null) {
			return;
		}

		if (!isset($schema->columns['org_visible'])) {
			$this->addColumn(
				self::FIELD_TABLE,
				'org_visible',
				$this->tinyInteger(1)->unsigned()->notNull()->defaultValue(0)->after('readonly')
			);
		}

		if ($this->db->getSchema()->getTableSchema(self::LEGACY_TABLE, true) !== null) {
			$this->db->createCommand(
				'UPDATE ' . self::FIELD_TABLE . ' vf
				INNER JOIN ' . self::LEGACY_TABLE . ' dof ON dof.fieldid = vf.fieldid
				SET vf.org_visible = dof.visible'
			)->execute();

			$this->dropTable(self::LEGACY_TABLE);
		}
	}

	public function safeDown(): void
	{
		$schema = $this->db->getSchema()->getTableSchema(self::FIELD_TABLE, true);
		if ($schema === null) {
			return;
		}

		if ($this->db->getSchema()->getTableSchema(self::LEGACY_TABLE, true) === null) {
			$this->createTable(self::LEGACY_TABLE, [
				'tabid' => $this->integer(),
				'fieldid' => $this->integer()->notNull(),
				'visible' => $this->integer(),
				'readonly' => $this->integer(),
			]);
			$this->addPrimaryKey('def_org_field_pk', self::LEGACY_TABLE, 'fieldid');
			$this->createIndex('def_org_field_tabid_fieldid_idx', self::LEGACY_TABLE, ['tabid', 'fieldid']);
			$this->createIndex('def_org_field_tabid_idx', self::LEGACY_TABLE, 'tabid');
			$this->createIndex('def_org_field_visible_fieldid_idx', self::LEGACY_TABLE, ['visible', 'fieldid']);

			$rows = (new Query())
				->select(['tabid', 'fieldid', 'org_visible'])
				->from(self::FIELD_TABLE)
				->all($this->db);
			foreach ($rows as $row) {
				$this->insert(self::LEGACY_TABLE, [
					'tabid' => $row['tabid'],
					'fieldid' => $row['fieldid'],
					'visible' => $row['org_visible'],
					'readonly' => 0,
				]);
			}
		}

		if (isset($schema->columns['org_visible'])) {
			$this->dropColumn(self::FIELD_TABLE, 'org_visible');
		}
	}
}
