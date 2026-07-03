<?php
/**
 * FreeCRM - Mail module profile permissions missing from initial schema migration.
 *
 * Run via: docker compose exec -T app php yii migrate --migrationPath=migrations/Users/ --interactive=0
 */

declare(strict_types=1);

use yii\db\Migration;
use yii\db\Query;

class m260703_000001_mail_profile_permissions extends Migration
{
	private const MAIL_TABID = 130;

	public function safeUp(): void
	{
		$tabExists = (new Query())
			->from('vtiger_tab')
			->where(['tabid' => self::MAIL_TABID, 'name' => 'Mail'])
			->exists($this->db);
		if (!$tabExists) {
			return;
		}

		$profileIds = (new Query())->select('profileid')->from('vtiger_profile')->column($this->db);
		foreach ($profileIds as $profileId) {
			$this->upsert('vtiger_profile2tab', [
				'profileid' => $profileId,
				'tabid' => self::MAIL_TABID,
			], [
				'permissions' => 0,
			]);
		}
	}

	public function safeDown(): void
	{
		$this->delete('vtiger_profile2tab', ['tabid' => self::MAIL_TABID]);
	}
}
