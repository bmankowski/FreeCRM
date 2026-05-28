<?php
/**
 * FreeCRM - Backfill DocumentTemplates crmentity rows and add FK to vtiger_crmentity.
 *
 * Run via: yii migrate --migrationPath=migrations/Users/
 */

declare(strict_types=1);

use yii\db\Migration;
use yii\db\Query;

class m260528_000001_documenttemplates_crmentity_fk extends Migration
{
	public function safeUp(): void
	{
		if ($this->db->getTableSchema('u_yf_documenttemplates', true) === null) {
			return;
		}
		$this->backfillCrmentityDocumentTemplates();
		$this->fixWrongSetypeRows();
		$this->addDocumentTemplatesForeignKey();
	}

	public function safeDown(): void
	{
		if ($this->db->getTableSchema('u_yf_documenttemplates', true) === null) {
			return;
		}
		$schema = $this->db->getTableSchema('u_yf_documenttemplates', true);
		if ($schema !== null) {
			foreach ($schema->foreignKeys as $fk) {
				if ($fk[1] === 'vtiger_crmentity' && in_array('documenttemplatesid', $fk, true)) {
					$this->dropForeignKey($fk[0], 'u_yf_documenttemplates');
				}
			}
		}
	}

	private function backfillCrmentityDocumentTemplates(): void
	{
		$rows = (new Query())
			->select(['documenttemplatesid'])
			->from('u_yf_documenttemplates')
			->all($this->db);
		$now = date('Y-m-d H:i:s');
		foreach ($rows as $row) {
			$id = (int) $row['documenttemplatesid'];
			$existing = (new Query())
				->from('vtiger_crmentity')
				->where(['crmid' => $id])
				->one($this->db);
			if ($existing !== false) {
				if (($existing['setype'] ?? '') !== 'DocumentTemplates') {
					$this->update('vtiger_crmentity', ['setype' => 'DocumentTemplates'], ['crmid' => $id]);
				}
				continue;
			}
			$this->insert('vtiger_crmentity', [
				'crmid' => $id,
				'smcreatorid' => 1,
				'smownerid' => 1,
				'shownerid' => 0,
				'modifiedby' => 1,
				'setype' => 'DocumentTemplates',
				'description' => null,
				'createdtime' => $now,
				'modifiedtime' => $now,
				'presence' => 1,
				'deleted' => 0,
				'was_read' => 0,
				'private' => 0,
			]);
		}
	}

	private function fixWrongSetypeRows(): void
	{
		$ids = (new Query())
			->select(['d.documenttemplatesid'])
			->from(['d' => 'u_yf_documenttemplates'])
			->innerJoin(['c' => 'vtiger_crmentity'], 'c.crmid = d.documenttemplatesid')
			->where(['<>', 'c.setype', 'DocumentTemplates'])
			->column($this->db);
		if ($ids !== []) {
			$this->update('vtiger_crmentity', ['setype' => 'DocumentTemplates'], ['crmid' => $ids]);
		}
	}

	private function addDocumentTemplatesForeignKey(): void
	{
		$schema = $this->db->getTableSchema('u_yf_documenttemplates', true);
		if ($schema === null) {
			return;
		}
		foreach ($schema->foreignKeys as $fk) {
			if ($fk[1] === 'vtiger_crmentity' && in_array('documenttemplatesid', $fk, true)) {
				return;
			}
		}
		$column = (new Query())
			->select(['COLUMN_TYPE'])
			->from('information_schema.COLUMNS')
			->where([
				'TABLE_SCHEMA' => $this->db->createCommand('SELECT DATABASE()')->queryScalar(),
				'TABLE_NAME' => 'u_yf_documenttemplates',
				'COLUMN_NAME' => 'documenttemplatesid',
			])
			->scalar($this->db);
		if ($column !== false && stripos((string) $column, 'unsigned') !== false) {
			$this->alterColumn('u_yf_documenttemplates', 'documenttemplatesid', $this->integer()->notNull());
		}
		$this->addForeignKey(
			'fk_u_yf_documenttemplates_crmid',
			'u_yf_documenttemplates',
			'documenttemplatesid',
			'vtiger_crmentity',
			'crmid',
			'CASCADE',
			'RESTRICT'
		);
	}
}
