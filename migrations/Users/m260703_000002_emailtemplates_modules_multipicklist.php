<?php
/**
 * EmailTemplates: single module column → modules (uitype 359 ModulesMultipicklist).
 *
 * Run via: docker compose exec -T app php yii migrate --migrationPath=migrations/Users/ --interactive=0
 */

declare(strict_types=1);

use yii\db\Migration;
use yii\db\Query;

class m260703_000002_emailtemplates_modules_multipicklist extends Migration
{
	private const EMAIL_TEMPLATES_TABID = 112;
	private const FIELD_MODULES = 2480;
	private const OLD_CV_COLUMN = 'u_yf_emailtemplates:module:module_name:EmailTemplates_FL_MODULE:V';
	private const NEW_CV_COLUMN = 'u_yf_emailtemplates:modules:modules:EmailTemplates_FL_MODULES:V';

	public function safeUp(): void
	{
		$this->migrateColumn();
		$this->updateFieldMetadata();
		$this->updateCustomViewColumns();
		$this->clearCaches();
	}

	public function safeDown(): void
	{
		echo "m260703_000002: safeDown not supported — restore DB backup.\n";
	}

	private function migrateColumn(): void
	{
		$schema = $this->db->schema->getTableSchema('u_yf_emailtemplates', true);
		if ($schema === null) {
			return;
		}

		if (isset($schema->columns['modules'])) {
			if (isset($schema->columns['module'])) {
				$this->db->createCommand(
					'UPDATE u_yf_emailtemplates SET modules = module
					WHERE (modules IS NULL OR TRIM(modules) = \'\')
					  AND module IS NOT NULL AND TRIM(module) <> \'\''
				)->execute();
				$this->dropColumn('u_yf_emailtemplates', 'module');
			}

			return;
		}

		$this->addColumn('u_yf_emailtemplates', 'modules', $this->text()->after('email_template_type'));

		if (isset($schema->columns['module'])) {
			$this->db->createCommand(
				'UPDATE u_yf_emailtemplates SET modules = module
				WHERE module IS NOT NULL AND TRIM(module) <> \'\''
			)->execute();
			$this->dropColumn('u_yf_emailtemplates', 'module');
		}
	}

	private function updateFieldMetadata(): void
	{
		$this->update(
			'vtiger_field',
			[
				'columnname' => 'modules',
				'fieldname' => 'modules',
				'fieldlabel' => 'FL_MODULES',
				'uitype' => 359,
				'typeofdata' => 'V',
				'mandatory' => 0,
			],
			['fieldid' => self::FIELD_MODULES, 'tabid' => self::EMAIL_TEMPLATES_TABID]
		);
	}

	private function updateCustomViewColumns(): void
	{
		foreach (['vtiger_cvcolumnlist', 'vtiger_cvadvfilter', 'vtiger_cvstdfilter'] as $table) {
			$this->update(
				$table,
				['columnname' => self::NEW_CV_COLUMN],
				['columnname' => self::OLD_CV_COLUMN]
			);
		}
	}

	private function clearCaches(): void
	{
		\App\Cache\Cache::delete('ModuleFields', (string) self::EMAIL_TEMPLATES_TABID);
		\App\Cache\Cache::delete('fieldInfo', (string) self::EMAIL_TEMPLATES_TABID);
		\App\Email\Mail::clearTemplateListCache();
	}
}
