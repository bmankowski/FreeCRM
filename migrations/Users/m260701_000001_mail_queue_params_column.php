<?php
/**
 * FreeCRM - Add params column to s_yf_mail_queue (sender_ref, mail_message_id JSON).
 *
 * Run via: docker compose exec -T app php yii migrate --migrationPath=migrations/Users/ --interactive=0
 */

declare(strict_types=1);

use yii\db\Migration;

class m260701_000001_mail_queue_params_column extends Migration
{
	private const TABLE = 's_yf_mail_queue';

	public function safeUp(): void
	{
		$schema = $this->db->schema->getTableSchema(self::TABLE, true);
		if ($schema === null || isset($schema->columns['params'])) {
			return;
		}

		$this->addColumn(self::TABLE, 'params', $this->text()->null()->after('priority'));
	}

	public function safeDown(): void
	{
		$schema = $this->db->schema->getTableSchema(self::TABLE, true);
		if ($schema === null || !isset($schema->columns['params'])) {
			return;
		}

		$this->dropColumn(self::TABLE, 'params');
	}
}
