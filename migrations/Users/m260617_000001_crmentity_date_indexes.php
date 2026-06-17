<?php
/**
 * FreeCRM - Composite indexes on vtiger_crmentity for module date scans/sorts.
 *
 * Run via: docker compose exec -T app php yii migrate --migrationPath=migrations/Users/ --interactive=0
 */

declare(strict_types=1);

use yii\db\Migration;

class m260617_000001_crmentity_date_indexes extends Migration
{
	private const TABLE = 'vtiger_crmentity';

	private const INDEXES = [
		'crmentity_setype_deleted_created_idx' => ['setype', 'deleted', 'createdtime'],
		'crmentity_setype_deleted_modified_idx' => ['setype', 'deleted', 'modifiedtime'],
	];

	public function safeUp(): void
	{
		$schema = $this->db->getSchema()->getTableSchema(self::TABLE, true);
		if ($schema === null) {
			return;
		}

		foreach (self::INDEXES as $name => $columns) {
			if (!isset($schema->indexes[$name])) {
				$this->createIndex($name, self::TABLE, $columns);
				echo "Created index $name on " . self::TABLE . "\n";
			}
		}
	}

	public function safeDown(): void
	{
		$schema = $this->db->getSchema()->getTableSchema(self::TABLE, true);
		if ($schema === null) {
			return;
		}

		foreach (array_keys(self::INDEXES) as $name) {
			if (isset($schema->indexes[$name])) {
				$this->dropIndex($name, self::TABLE);
			}
		}
	}
}
