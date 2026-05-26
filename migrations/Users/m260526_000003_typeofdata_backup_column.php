<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * Adds typeofdata_old as a temporary snapshot of typeofdata before further refactoring.
 *
 * Run via: yii migrate --migrationPath=migrations/Users/
 */

declare(strict_types=1);

use yii\db\Migration;

class m260526_000003_typeofdata_backup_column extends Migration
{
	private const TABLE = 'vtiger_field';

	public function safeUp(): void
	{
		$schema = $this->db->getSchema()->getTableSchema(self::TABLE, true);
		if ($schema === null) {
			return;
		}

		if (!isset($schema->columns['typeofdata_old'])) {
			$this->addColumn(
				self::TABLE,
				'typeofdata_old',
				$this->string(100)->null()->defaultValue(null)->after('typeofdata')
			);
		}

		$this->db->createCommand(
			'UPDATE ' . self::TABLE . ' SET typeofdata_old = typeofdata'
		)->execute();
	}

	public function safeDown(): void
	{
		$schema = $this->db->getSchema()->getTableSchema(self::TABLE, true);
		if ($schema !== null && isset($schema->columns['typeofdata_old'])) {
			$this->dropColumn(self::TABLE, 'typeofdata_old');
		}
	}
}
