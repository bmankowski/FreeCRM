<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * Adds dedicated mandatory column to vtiger_field (extracted from typeofdata segment 2).
 *
 * Run via: yii migrate --migrationPath=migrations/Users/
 */

declare(strict_types=1);

use yii\db\Migration;
use yii\db\Query;

class m260526_000001_vtiger_field_mandatory extends Migration
{
	private const TABLE = 'vtiger_field';
	private const INDEX = 'field_mandatory_idx';

	private function indexExists(): bool
	{
		return (new Query())
			->from('INFORMATION_SCHEMA.STATISTICS')
			->where([
				'TABLE_SCHEMA' => $this->db->createCommand('SELECT DATABASE()')->queryScalar(),
				'TABLE_NAME' => self::TABLE,
				'INDEX_NAME' => self::INDEX,
			])
			->exists();
	}

	public function safeUp(): void
	{
		$schema = $this->db->getSchema()->getTableSchema(self::TABLE, true);
		if ($schema === null) {
			return;
		}

		if (!isset($schema->columns['mandatory'])) {
			$this->addColumn(
				self::TABLE,
				'mandatory',
				$this->tinyInteger(1)->unsigned()->notNull()->defaultValue(0)->after('readonly')
			);
		}

		$this->db->createCommand(
			"UPDATE " . self::TABLE . "
			SET mandatory = IF(SUBSTRING_INDEX(SUBSTRING_INDEX(typeofdata, '~', 2), '~', -1) = 'M', 1, 0)"
		)->execute();

		$this->db->createCommand(
			"UPDATE " . self::TABLE . " SET typeofdata = 'D~O' WHERE typeofdata = 'D~0'"
		)->execute();

		if (!$this->indexExists()) {
			$this->createIndex(self::INDEX, self::TABLE, 'mandatory');
		}
	}

	public function safeDown(): void
	{
		$schema = $this->db->getSchema()->getTableSchema(self::TABLE, true);
		if ($schema === null) {
			return;
		}

		if (isset($schema->columns['mandatory'])) {
			if ($this->indexExists()) {
				$this->dropIndex(self::INDEX, self::TABLE);
			}
			$this->dropColumn(self::TABLE, 'mandatory');
		}
	}
}
