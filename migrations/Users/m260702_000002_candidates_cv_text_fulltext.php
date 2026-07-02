<?php
/**
 * FULLTEXT index on Candidates.cv_text for kanban skills search.
 *
 * Run via: docker compose exec -T app php yii migrate --migrationPath=migrations/Users/ --interactive=0
 */

declare(strict_types=1);

use yii\db\Migration;

class m260702_000002_candidates_cv_text_fulltext extends Migration
{
	private const TABLE = 'u_yf_candidatescf';
	private const INDEX = 'u_yf_candidatescf_cv_text_ft';

	public function safeUp(): void
	{
		if ($this->indexExists(self::INDEX)) {
			echo 'FULLTEXT index ' . self::INDEX . ' already exists on ' . self::TABLE . "\n";
			return;
		}

		$this->execute(
			'ALTER TABLE `' . self::TABLE . '` ADD FULLTEXT INDEX `' . self::INDEX . '` (`cv_text`)'
		);
		echo 'Created FULLTEXT index ' . self::INDEX . ' on ' . self::TABLE . "\n";
	}

	public function safeDown(): void
	{
		if (!$this->indexExists(self::INDEX)) {
			return;
		}

		$this->dropIndex(self::INDEX, self::TABLE);
	}

	private function indexExists(string $indexName): bool
	{
		return (bool) $this->db->createCommand(
			'SELECT 1 FROM information_schema.STATISTICS
			 WHERE TABLE_SCHEMA = DATABASE()
			   AND TABLE_NAME = :table
			   AND INDEX_NAME = :index
			 LIMIT 1',
			[':table' => self::TABLE, ':index' => $indexName]
		)->queryScalar();
	}
}
