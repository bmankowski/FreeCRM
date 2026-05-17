<?php
/**
 * FreeCRM - Rename u_yf_templates to u_yf_documenttemplates (align with u_yf_emailtemplates).
 *
 * Run via: yii migrate --migrationPath=migrations/Users/
 */

declare(strict_types=1);

use yii\db\Migration;

class m260517_000001_rename_u_yf_templates_to_documenttemplates extends Migration
{
	private const TABLE_OLD = 'u_yf_templates';
	private const TABLE_NEW = 'u_yf_documenttemplates';
	private const PK_OLD = 'templatesid';
	private const PK_NEW = 'documenttemplatesid';

	public function safeUp(): void
	{
		if ($this->db->getTableSchema(self::TABLE_OLD, true) === null) {
			return;
		}
		if ($this->db->getTableSchema(self::TABLE_NEW, true) !== null) {
			return;
		}
		$this->renameTable(self::TABLE_OLD, self::TABLE_NEW);
		$this->renameColumn(self::TABLE_NEW, self::PK_OLD, self::PK_NEW);
	}

	public function safeDown(): void
	{
		if ($this->db->getTableSchema(self::TABLE_NEW, true) === null) {
			return;
		}
		if ($this->db->getTableSchema(self::TABLE_OLD, true) !== null) {
			return;
		}
		$this->renameColumn(self::TABLE_NEW, self::PK_NEW, self::PK_OLD);
		$this->renameTable(self::TABLE_NEW, self::TABLE_OLD);
	}
}
