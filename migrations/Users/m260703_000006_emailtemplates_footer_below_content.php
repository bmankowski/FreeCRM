<?php
/**
 * EmailTemplates: place footer field below content (sequence + full-width uitype 19).
 *
 * Run via: docker compose exec -T app php yii migrate --migrationPath=migrations/Users/ --interactive=0
 */

declare(strict_types=1);

use yii\db\Migration;
use yii\db\Query;

class m260703_000006_emailtemplates_footer_below_content extends Migration
{
	private const TABID = 112;
	private const CONTENT_BLOCK = 377;
	private const FOOTER_FIELD_ID = 303434;

	public function safeUp(): void
	{
		if (!(new Query())->from('vtiger_field')->where(['fieldid' => self::FOOTER_FIELD_ID])->exists()) {
			return;
		}

		$this->update('vtiger_field', ['sequence' => 1], [
			'tabid' => self::TABID,
			'block' => self::CONTENT_BLOCK,
			'fieldname' => 'subject',
		]);
		$this->update('vtiger_field', ['sequence' => 2], [
			'tabid' => self::TABID,
			'block' => self::CONTENT_BLOCK,
			'fieldname' => 'content',
		]);
		$this->update('vtiger_field', ['uitype' => 300, 'sequence' => 3], ['fieldid' => self::FOOTER_FIELD_ID]);

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

		$this->update('vtiger_field', ['sequence' => 0], [
			'tabid' => self::TABID,
			'block' => self::CONTENT_BLOCK,
			'fieldname' => ['subject', 'content'],
		]);
		$this->update('vtiger_field', ['uitype' => 19, 'sequence' => 0], ['fieldid' => self::FOOTER_FIELD_ID]);

		\App\Cache\Cache::delete('ModuleFields', (string) self::TABID);
		\App\Cache\Cache::delete('fieldInfo', (string) self::TABID);
	}
}
