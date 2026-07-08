<?php
/**
 * FreeCRM - Group mailboxes: Reply-To should match From (group address), not sender's personal email.
 *
 * Run via: docker compose exec -T app php yii migrate --migrationPath=migrations/Users/ --interactive=0
 */

declare(strict_types=1);

use yii\db\Migration;

class m260708_000002_group_mailbox_reply_to_mode extends Migration
{
	public function safeUp(): void
	{
		$this->update(
			'u_yf_mail_accounts',
			['reply_to_mode' => 'same_as_from'],
			['and', ['kind' => 'group'], ['reply_to_mode' => 'user_personal']]
		);
	}

	public function safeDown(): void
	{
		$this->update(
			'u_yf_mail_accounts',
			['reply_to_mode' => 'user_personal'],
			['and', ['kind' => 'group'], ['reply_to_mode' => 'same_as_from']]
		);
	}
}
