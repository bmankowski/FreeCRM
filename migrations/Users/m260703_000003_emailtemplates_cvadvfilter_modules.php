<?php
/**
 * EmailTemplates: fix custom-view filters still referencing removed module_name column.
 *
 * Run via: docker compose exec -T app php yii migrate --migrationPath=migrations/Users/ --interactive=0
 */

declare(strict_types=1);

use yii\db\Migration;

class m260703_000003_emailtemplates_cvadvfilter_modules extends Migration
{
	private const OLD_CV_COLUMN = 'u_yf_emailtemplates:module:module_name:EmailTemplates_FL_MODULE:V';
	private const NEW_CV_COLUMN = 'u_yf_emailtemplates:modules:modules:EmailTemplates_FL_MODULES:V';

	public function safeUp(): void
	{
		foreach (['vtiger_cvcolumnlist', 'vtiger_cvadvfilter', 'vtiger_cvstdfilter'] as $table) {
			$this->update(
				$table,
				['columnname' => self::NEW_CV_COLUMN],
				['columnname' => self::OLD_CV_COLUMN]
			);
		}

		\App\Cache\Cache::delete('ModuleFields', '112');
		\App\Cache\Cache::delete('fieldInfo', '112');
	}

	public function safeDown(): void
	{
		echo "m260703_000003: safeDown not supported — restore DB backup.\n";
	}
}
