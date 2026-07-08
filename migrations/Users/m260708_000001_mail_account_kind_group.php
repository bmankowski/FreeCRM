<?php
/**
 * FreeCRM - Rename mail account kind shared → group.
 *
 * Run via: docker compose exec -T app php yii migrate --migrationPath=migrations/Users/ --interactive=0
 */

declare(strict_types=1);

use yii\db\Migration;

class m260708_000001_mail_account_kind_group extends Migration
{
	public function safeUp(): void
	{
		$schema = $this->db->getSchema()->getTableSchema('u_yf_mail_accounts', true);
		if ($schema === null || !isset($schema->columns['kind'])) {
			return;
		}

		$column = $schema->columns['kind'];
		$dbType = strtolower((string) ($column->dbType ?? ''));
		if (str_contains($dbType, "'shared'")) {
			$this->execute(
				"ALTER TABLE u_yf_mail_accounts MODIFY COLUMN kind ENUM('personal','shared','group') NOT NULL"
			);
			$this->update('u_yf_mail_accounts', ['kind' => 'group'], ['kind' => 'shared']);
			$this->execute(
				"ALTER TABLE u_yf_mail_accounts MODIFY COLUMN kind ENUM('personal','group') NOT NULL"
			);
		}
	}

	public function safeDown(): void
	{
		$schema = $this->db->getSchema()->getTableSchema('u_yf_mail_accounts', true);
		if ($schema === null || !isset($schema->columns['kind'])) {
			return;
		}

		$column = $schema->columns['kind'];
		$dbType = strtolower((string) ($column->dbType ?? ''));
		if (str_contains($dbType, "'group'") && !str_contains($dbType, "'shared'")) {
			$this->execute(
				"ALTER TABLE u_yf_mail_accounts MODIFY COLUMN kind ENUM('personal','shared','group') NOT NULL"
			);
			$this->update('u_yf_mail_accounts', ['kind' => 'shared'], ['kind' => 'group']);
			$this->execute(
				"ALTER TABLE u_yf_mail_accounts MODIFY COLUMN kind ENUM('personal','shared') NOT NULL"
			);
		}
	}
}
