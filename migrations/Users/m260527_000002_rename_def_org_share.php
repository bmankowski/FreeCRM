<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * Replaces vtiger_def_org_share with vtiger_tab_sharing_default (tabid PK, no editstatus).
 *
 * Run via: yii migrate --migrationPath=migrations/Users/
 */

declare(strict_types=1);

use yii\db\Migration;
use yii\db\Query;

class m260527_000002_rename_def_org_share extends Migration
{
	private const LEGACY_TABLE = 'vtiger_def_org_share';
	private const TABLE = 'vtiger_tab_sharing_default';

	public function safeUp(): void
	{
		if ($this->db->getSchema()->getTableSchema(self::TABLE, true) !== null) {
			return;
		}

		if ($this->db->getSchema()->getTableSchema(self::LEGACY_TABLE, true) === null) {
			$this->createTable(self::TABLE, [
				'tabid' => $this->integer()->notNull(),
				'permission' => $this->integer()->notNull(),
			]);
			$this->addPrimaryKey('pk_vtiger_tab_sharing_default', self::TABLE, 'tabid');
			$this->addForeignKey(
				'fk_vtiger_tab_sharing_default_tabid',
				self::TABLE,
				'tabid',
				'vtiger_tab',
				'tabid',
				'CASCADE',
				'RESTRICT'
			);
			$this->addForeignKey(
				'fk_vtiger_tab_sharing_default_permission',
				self::TABLE,
				'permission',
				'vtiger_org_share_action_mapping',
				'share_action_id',
				'CASCADE',
				'RESTRICT'
			);
			return;
		}

		$this->createTable(self::TABLE, [
			'tabid' => $this->integer()->notNull(),
			'permission' => $this->integer()->notNull(),
		]);
		$this->addPrimaryKey('pk_vtiger_tab_sharing_default', self::TABLE, 'tabid');

		$this->db->createCommand(
			'INSERT INTO ' . self::TABLE . ' (tabid, permission)
			SELECT s.tabid, s.permission
			FROM ' . self::LEGACY_TABLE . ' s
			INNER JOIN (
				SELECT tabid, MAX(ruleid) AS ruleid
				FROM ' . self::LEGACY_TABLE . '
				GROUP BY tabid
			) latest ON latest.tabid = s.tabid AND latest.ruleid = s.ruleid'
		)->execute();

		$this->addForeignKey(
			'fk_vtiger_tab_sharing_default_tabid',
			self::TABLE,
			'tabid',
			'vtiger_tab',
			'tabid',
			'CASCADE',
			'RESTRICT'
		);
		$this->addForeignKey(
			'fk_vtiger_tab_sharing_default_permission',
			self::TABLE,
			'permission',
			'vtiger_org_share_action_mapping',
			'share_action_id',
			'CASCADE',
			'RESTRICT'
		);

		$this->dropTable(self::LEGACY_TABLE);
	}

	public function safeDown(): void
	{
		if ($this->db->getSchema()->getTableSchema(self::LEGACY_TABLE, true) !== null) {
			return;
		}

		if ($this->db->getSchema()->getTableSchema(self::TABLE, true) === null) {
			return;
		}

		$this->createTable(self::LEGACY_TABLE, [
			'ruleid' => $this->primaryKey(),
			'tabid' => $this->integer()->notNull(),
			'permission' => $this->integer(),
			'editstatus' => $this->integer(),
		]);
		$this->createIndex('fk_1_vtiger_def_org_share', self::LEGACY_TABLE, 'permission');
		$this->addForeignKey(
			'fk_1_vtiger_def_org_share',
			self::LEGACY_TABLE,
			'permission',
			'vtiger_org_share_action_mapping',
			'share_action_id',
			'CASCADE',
			'RESTRICT'
		);

		$lockedNames = \App\Security\ModuleSharingDefault::LOCKED_MODULE_NAMES;
		$lockedTabIds = (new Query())
			->select('tabid')
			->from('vtiger_tab')
			->where(['name' => $lockedNames])
			->column($this->db);
		$lockedTabIds = array_map('intval', $lockedTabIds);

		$rows = (new Query())
			->select(['tabid', 'permission'])
			->from(self::TABLE)
			->orderBy(['tabid' => SORT_ASC])
			->all($this->db);

		$ruleId = 1;
		foreach ($rows as $row) {
			$tabId = (int) $row['tabid'];
			$this->insert(self::LEGACY_TABLE, [
				'ruleid' => $ruleId++,
				'tabid' => $tabId,
				'permission' => $row['permission'],
				'editstatus' => in_array($tabId, $lockedTabIds, true) ? 2 : 0,
			]);
		}

		$this->dropForeignKey('fk_vtiger_tab_sharing_default_permission', self::TABLE);
		$this->dropForeignKey('fk_vtiger_tab_sharing_default_tabid', self::TABLE);
		$this->dropTable(self::TABLE);
	}
}
