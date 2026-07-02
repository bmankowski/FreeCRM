<?php
/**
 * FreeCRM - HelpDesk parent_id reference order: Accounts before Vendors.
 *
 * NULL sequence sorts first in ORDER BY sequence ASC, so Vendors appeared before Accounts.
 *
 * Run via: docker compose exec -T app php yii migrate --migrationPath=migrations/Users/ --interactive=0
 */

declare(strict_types=1);

use yii\db\Migration;

class m260702_000001_helpdesk_parent_id_accounts_before_vendors extends Migration
{
	private const FIELD_ID = 157;

	public function safeUp(): void
	{
		$this->update(
			'vtiger_fieldmodulerel',
			['sequence' => 1],
			['fieldid' => self::FIELD_ID, 'relmodule' => 'Accounts']
		);
		$this->update(
			'vtiger_fieldmodulerel',
			['sequence' => 2],
			['fieldid' => self::FIELD_ID, 'relmodule' => 'Vendors']
		);
		$this->clearReferenceListCache();
	}

	public function safeDown(): void
	{
		$this->update(
			'vtiger_fieldmodulerel',
			['sequence' => 1],
			['fieldid' => self::FIELD_ID, 'relmodule' => 'Accounts']
		);
		$this->update(
			'vtiger_fieldmodulerel',
			['sequence' => null],
			['fieldid' => self::FIELD_ID, 'relmodule' => 'Vendors']
		);
		$this->clearReferenceListCache();
	}

	private function clearReferenceListCache(): void
	{
		if (!class_exists(\App\Cache\Cache::class)) {
			return;
		}
		\App\Cache\Cache::init();
		\App\Cache\Cache::delete('getReferenceList', self::FIELD_ID);
	}
}
