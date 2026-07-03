<?php
/**
 * EmailTemplates: footer field uitype 300 (full-width HTML editor, same as content).
 *
 * Run via: docker compose exec -T app php yii migrate --migrationPath=migrations/Users/ --interactive=0
 */

declare(strict_types=1);

use yii\db\Migration;
use yii\db\Query;

class m260703_000007_emailtemplates_footer_html_editor extends Migration
{
	private const TABID = 112;
	private const FOOTER_FIELD_ID = 303434;

	public function safeUp(): void
	{
		if (!(new Query())->from('vtiger_field')->where(['fieldid' => self::FOOTER_FIELD_ID])->exists()) {
			return;
		}

		$this->update('vtiger_field', ['uitype' => 300], ['fieldid' => self::FOOTER_FIELD_ID]);

		\App\Cache\Cache::delete('ModuleFields', (string) self::TABID);
		\App\Cache\Cache::delete('fieldInfo', (string) self::TABID);
		\App\Cache\Cache::delete('field-' . self::TABID, self::FOOTER_FIELD_ID);
		\App\Cache\Cache::delete('field-' . self::TABID, 'footer');
	}

	public function safeDown(): void
	{
		if (!(new Query())->from('vtiger_field')->where(['fieldid' => self::FOOTER_FIELD_ID])->exists()) {
			return;
		}

		$this->update('vtiger_field', ['uitype' => 19], ['fieldid' => self::FOOTER_FIELD_ID]);

		\App\Cache\Cache::delete('ModuleFields', (string) self::TABID);
		\App\Cache\Cache::delete('fieldInfo', (string) self::TABID);
	}
}
