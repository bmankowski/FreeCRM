<?php
/**
 * FreeCRM - Normalize internal document storage_path to id-only blob keys.
 *
 * Run via: docker compose exec -T app php yii migrate --migrationPath=migrations/Users/ --interactive=0
 */

declare(strict_types=1);

use yii\db\Migration;

class m260615_000001_documents_storage_path_id_only extends Migration
{
	public function safeUp(): void
	{
		$this->db->createCommand(<<<'SQL'
UPDATE vtiger_notes
SET storage_path = CONCAT(
  LEFT(storage_path, CHAR_LENGTH(storage_path) - CHAR_LENGTH(SUBSTRING_INDEX(storage_path, '/', -1))),
  SUBSTRING_INDEX(SUBSTRING_INDEX(storage_path, '/', -1), '_', 1)
)
WHERE location_type = 'internal'
  AND storage_path IS NOT NULL
  AND storage_path != ''
  AND SUBSTRING_INDEX(storage_path, '/', -1) REGEXP '^[0-9]+_.+'
SQL)->execute();
	}

	public function safeDown(): void
	{
		echo "m260615_000001_documents_storage_path_id_only: safeDown not supported — restore DB backup.\n";
	}
}
