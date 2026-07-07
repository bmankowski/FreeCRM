<?php
/**
 * FreeCRM - Provision personal mail account row when a user gets email1.
 *
 * Run via: docker compose exec -T app php yii migrate --migrationPath=migrations/Users/ --interactive=0
 */

declare(strict_types=1);

use yii\db\Migration;
use yii\db\Query;

class m260707_000002_mail_personal_account_provision extends Migration
{
	private const EVENT_NAME = 'EntityAfterSave';
	private const HANDLER_CLASS = 'Mail_PersonalMailboxProvision_Handler';
	private const INCLUDE_MODULES = 'Users';

	public function safeUp(): void
	{
		\App\Events\EventHandler::registerHandler(
			self::EVENT_NAME,
			self::HANDLER_CLASS,
			self::INCLUDE_MODULES
		);

		$userIds = (new Query())
			->select(['id'])
			->from('vtiger_users')
			->where(['status' => 'Active'])
			->andWhere(['not', ['email1' => null]])
			->andWhere(['<>', 'email1', ''])
			->column();

		foreach ($userIds as $userId) {
			\App\Modules\Mail\Models\Account::ensurePersonalAccountForUser((int) $userId);
		}
	}

	public function safeDown(): void
	{
		\App\Events\EventHandler::deleteHandler(self::HANDLER_CLASS, self::EVENT_NAME);
	}
}
