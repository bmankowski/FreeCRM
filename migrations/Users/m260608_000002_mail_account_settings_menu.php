<?php
/**
 * FreeCRM - Fix Konta pocztowe under Settings → Narzędzia pocztowe.
 *
 * Run via: docker compose exec -T app php yii migrate --migrationPath=migrations/Users/ --interactive=0
 */

declare(strict_types=1);

use yii\db\Migration;
use yii\db\Query;

class m260608_000002_mail_account_settings_menu extends Migration
{
	private const SETTINGS_BLOCK = 'LBL_MAIL_TOOLS';

	public function safeUp(): void
	{
		$blockId = (int) \vtlib\Deprecated::getSettingsBlockId(self::SETTINGS_BLOCK);
		if ($blockId <= 0) {
			return;
		}

		$sequence = (int) ((new Query())
			->from('vtiger_settings_field')
			->where(['blockid' => $blockId])
			->max('sequence') ?: 0) + 1;

		$exists = (new Query())
			->from('vtiger_settings_field')
			->where(['name' => 'LBL_MAIL_ACCOUNTS'])
			->one();

		if ($exists) {
			$this->update('vtiger_settings_field', [
				'blockid' => $blockId,
				'sequence' => (int) ($exists['sequence'] ?? $sequence) ?: $sequence,
				'active' => 0,
			], ['fieldid' => (int) $exists['fieldid']]);
			return;
		}

		\App\Modules\Settings\Base\Models\Module::addSettingsField(self::SETTINGS_BLOCK, [
			'name' => 'LBL_MAIL_ACCOUNTS',
			'iconpath' => 'adminIcon-mail-scanner',
			'description' => 'LBL_MAIL_ACCOUNTS_DESCRIPTION',
			'linkto' => 'index.php?module=MailAccount&parent=Settings&view=List',
		]);
	}

	public function safeDown(): void
	{
		\App\Modules\Settings\Base\Models\Module::deleteSettingsField(self::SETTINGS_BLOCK, 'LBL_MAIL_ACCOUNTS');
	}
}
