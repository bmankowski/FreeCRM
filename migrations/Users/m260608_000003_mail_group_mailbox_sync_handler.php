<?php
/**
 * FreeCRM - Sync shared mail account members when CRM group membership changes.
 *
 * Run via: docker compose exec -T app php yii migrate --migrationPath=migrations/Users/ --interactive=0
 */

declare(strict_types=1);

use yii\db\Migration;
use yii\db\Query;

class m260608_000003_mail_group_mailbox_sync_handler extends Migration
{
	public function safeUp(): void
	{
		\App\Events\EventHandler::registerHandler('GroupAfterSave', 'Mail_GroupMailboxSync_Handler');

		foreach ((new Query())
			->select(['id', 'group_id'])
			->from('u_yf_mail_accounts')
			->where(['kind' => 'group'])
			->andWhere(['not', ['group_id' => null]])
			->all() as $row) {
			\App\Modules\Mail\Models\Account::syncGroupMembers((int) $row['id'], (int) $row['group_id']);
		}
	}

	public function safeDown(): void
	{
		\App\Events\EventHandler::deleteHandler('Mail_GroupMailboxSync_Handler', 'GroupAfterSave');
	}
}
