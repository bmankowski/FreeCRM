<?php
/**
 * FreeCRM - Add delivery_mode to recruitment status transition mail matrix.
 *
 * Run via: docker compose exec -T app php yii migrate --migrationPath=migrations/Users/ --interactive=0
 */

declare(strict_types=1);

use yii\db\Migration;

class m260622_000001_recruitment_transition_mail_delivery_mode extends Migration
{
	private const TABLE = 'u_yf_recruitment_status_transition_mail';

	public function safeUp(): void
	{
		$schema = $this->db->schema->getTableSchema(self::TABLE, true);
		if ($schema === null || isset($schema->columns['delivery_mode'])) {
			return;
		}

		$this->addColumn(
			self::TABLE,
			'delivery_mode',
			$this->string(10)->notNull()->defaultValue('prompt')->after('short_name')
		);
	}

	public function safeDown(): void
	{
		$schema = $this->db->schema->getTableSchema(self::TABLE, true);
		if ($schema === null || !isset($schema->columns['delivery_mode'])) {
			return;
		}

		$this->dropColumn(self::TABLE, 'delivery_mode');
	}
}
