<?php
/**
 * FreeCRM - allow programmatic save of report_issue_url (displaytype=2 keeps UI read-only).
 *
 * Run via: yii migrate --migrationPath=migrations/Users/
 */

declare(strict_types=1);

use yii\db\Migration;

class m260701_000001_report_issue_url_writable extends Migration
{
	private const FIELD_ID = 303013;
	private const TABID = 13;

	public function safeUp(): void
	{
		$this->update('vtiger_field', ['readonly' => 0], ['fieldid' => self::FIELD_ID]);
		$this->update('vtiger_profile2field', ['readonly' => 0], ['fieldid' => self::FIELD_ID]);
		$this->clearHelpDeskFieldCache();
	}

	public function safeDown(): void
	{
		$this->update('vtiger_field', ['readonly' => 1], ['fieldid' => self::FIELD_ID]);
		$this->update('vtiger_profile2field', ['readonly' => 1], ['fieldid' => self::FIELD_ID]);
		$this->clearHelpDeskFieldCache();
	}

	private function clearHelpDeskFieldCache(): void
	{
		if (!class_exists(\App\Fields\Field::class)) {
			return;
		}
		\App\Fields\Field::clearFieldsPermissionsCacheForTab(self::TABID);
		if (class_exists(\App\Cache\Cache::class)) {
			\App\Cache\Cache::init();
			\App\Cache\Cache::delete('ModuleFields', self::TABID);
			\App\Cache\Cache::delete('fieldInfo', self::TABID);
		}
	}
}
