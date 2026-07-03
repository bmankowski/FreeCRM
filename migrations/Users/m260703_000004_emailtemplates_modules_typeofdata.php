<?php
/**
 * EmailTemplates modules field: normalize typeofdata for custom-view operator mapping.
 *
 * Run via: docker compose exec -T app php yii migrate --migrationPath=migrations/Users/ --interactive=0
 */

declare(strict_types=1);

use yii\db\Migration;

class m260703_000004_emailtemplates_modules_typeofdata extends Migration
{
	private const EMAIL_TEMPLATES_TABID = 112;

	public function safeUp(): void
	{
		$this->update(
			'vtiger_field',
			['typeofdata' => 'V'],
			['tabid' => self::EMAIL_TEMPLATES_TABID, 'fieldname' => 'modules', 'typeofdata' => 'V~O']
		);

		\App\Cache\Cache::delete('ModuleFields', (string) self::EMAIL_TEMPLATES_TABID);
		\App\Cache\Cache::delete('fieldInfo', (string) self::EMAIL_TEMPLATES_TABID);
	}

	public function safeDown(): void
	{
		echo "m260703_000004: safeDown not supported — restore DB backup.\n";
	}
}
