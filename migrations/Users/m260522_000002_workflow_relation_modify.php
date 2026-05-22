<?php
/**
 * FreeCRM - Relation workflow tables and recruitment_status_rel vtiger_field metadata.
 *
 * Run via: yii migrate --migrationPath=migrations/Users/
 */

declare(strict_types=1);

use yii\db\Migration;
use yii\db\Query;

class m260522_000002_workflow_relation_modify extends Migration
{
	private const RELATION_TABLE = 'u_yf_projekty_rekrutacyjne_relations_members_entity';
	private const PROJEKTY_TABID = 119;
	private const PROJEKTY_ENTITY_TABLE = 'u_yf_projektyrekrutacyjne';

	public function safeUp(): void
	{
		$schema = $this->db->schema;

		if ($schema->getTableSchema('com_vtiger_workflow_relation_triggers', true) === null) {
			$this->createTable('com_vtiger_workflow_relation_triggers', [
				'id' => $this->primaryKey()->unsigned(),
				'workflow_id' => $this->integer()->notNull(),
				'source_module' => $this->string(100)->notNull(),
				'destination_module' => $this->string(100)->notNull(),
				'relation_table' => $this->string(200)->notNull(),
				'relation_field' => $this->string(100)->notNull(),
				'source_value' => $this->string(255)->null(),
				'destination_value' => $this->string(255)->notNull(),
				'once_per_pair' => $this->smallInteger(1)->notNull()->defaultValue(0),
			], 'ENGINE=InnoDB DEFAULT CHARSET=utf8');
			$this->createIndex(
				'com_vtiger_workflow_relation_triggers_workflow_id',
				'com_vtiger_workflow_relation_triggers',
				'workflow_id',
				true
			);
		}

		if ($schema->getTableSchema('com_vtiger_workflow_relation_activatedonce', true) === null) {
			$this->createTable('com_vtiger_workflow_relation_activatedonce', [
				'workflow_id' => $this->integer()->notNull(),
				'source_record_id' => $this->integer()->notNull(),
				'destination_record_id' => $this->integer()->notNull(),
			], 'ENGINE=InnoDB DEFAULT CHARSET=utf8');
			$this->addPrimaryKey(
				'com_vtiger_workflow_relation_activatedonce_pk',
				'com_vtiger_workflow_relation_activatedonce',
				['workflow_id', 'source_record_id', 'destination_record_id']
			);
		}

		$this->registerRelationFieldMetadata();
	}

	public function safeDown(): void
	{
		$this->delete('vtiger_field', [
			'fieldname' => 'recruitment_status_rel',
			'tabid' => self::PROJEKTY_TABID,
		]);

		$this->dropTable('com_vtiger_workflow_relation_activatedonce');
		$this->dropTable('com_vtiger_workflow_relation_triggers');
	}

	private function registerRelationFieldMetadata(): void
	{
		$exists = (new Query())
			->from('vtiger_field')
			->where([
				'tabid' => self::PROJEKTY_TABID,
				'fieldname' => 'recruitment_status_rel',
				'tablename' => self::RELATION_TABLE,
			])
			->exists();

		if ($exists) {
			$this->update('vtiger_field', [
				'helpinfo' => 'relation_only:1',
				'fieldparams' => 'relation_table=' . self::RELATION_TABLE,
				'displaytype' => 2,
				'presence' => 1,
			], [
				'tabid' => self::PROJEKTY_TABID,
				'fieldname' => 'recruitment_status_rel',
			]);
			return;
		}

		$fieldId = (int) (new Query())
			->from('vtiger_field')
			->max('fieldid');
		++$fieldId;

		$blockId = (new Query())
			->from('vtiger_blocks')
			->where(['tabid' => self::PROJEKTY_TABID])
			->orderBy(['sequence' => SORT_ASC])
			->select('blockid')
			->scalar();
		if (!$blockId) {
			return;
		}

		$this->insert('vtiger_field', [
			'fieldid' => $fieldId,
			'tabid' => self::PROJEKTY_TABID,
			'columnname' => 'recruitment_status_rel',
			'tablename' => self::PROJEKTY_ENTITY_TABLE,
			'generatedtype' => 2,
			'uitype' => 115,
			'fieldname' => 'recruitment_status_rel',
			'fieldlabel' => 'LBL_STATUS_REL',
			'readonly' => 1,
			'presence' => 1,
			'defaultvalue' => '',
			'maximumlength' => 255,
			'sequence' => 999,
			'displaytype' => 2,
			'typeofdata' => 'V~O',
			'quickcreate' => 1,
			'quickcreatesequence' => 0,
			'info_type' => 'BAS',
			'masseditable' => 0,
			'helpinfo' => 'relation_only:1',
			'fieldparams' => 'relation_table=' . self::RELATION_TABLE,
			'block' => $blockId,
		]);
	}
}
