<?php
/**
 * FreeCRM - remove global mail signature config from yetiforce_mail_config.
 *
 * Run via: docker compose exec -T app php yii migrate --migrationPath=migrations/Users/ --interactive=0
 */

declare(strict_types=1);

use yii\db\Migration;

class m260603_000004_drop_global_mail_signature extends Migration
{
	public function safeUp(): void
	{
		$this->delete('yetiforce_mail_config', ['type' => 'signature']);
	}

	public function safeDown(): void
	{
		$rows = [
			['signature', 'signature', ''],
			['signature', 'addSignature', 'false'],
		];
		foreach ($rows as $row) {
			$exists = (new \yii\db\Query())
				->from('yetiforce_mail_config')
				->where(['type' => $row[0], 'name' => $row[1]])
				->exists($this->db);
			if (!$exists) {
				$this->insert('yetiforce_mail_config', [
					'type' => $row[0],
					'name' => $row[1],
					'value' => $row[2],
				]);
			}
		}
	}
}
