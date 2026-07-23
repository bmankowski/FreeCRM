<?php
/**
 * FreeCRM - Drop EmailTemplates type split (PLL_MAIL / PLL_RECORD).
 *
 * Run via: docker compose exec -T app php yii migrate --migrationPath=migrations/Users/ --interactive=0
 */

declare(strict_types=1);

use App\Modules\Base\Models\Field;
use App\Modules\Base\Models\Module;
use yii\db\Migration;

class m260723_000001_drop_email_template_type extends Migration
{
	public $transaction = false;

	private const MODULE_NAME = 'EmailTemplates';
	private const TABID = 112;
	private const FIELD_NAME = 'email_template_type';
	private const TABLE = 'u_yf_emailtemplates';
	private const PICKLIST_TABLE = 'vtiger_email_template_type';
	private const CV_COLUMN = 'u_yf_emailtemplates:email_template_type:email_template_type:EmailTemplates_FL_TYPE:V';

	public function safeUp(): void
	{
		$this->removeCustomViewReferences();
		$this->deleteFieldMetadata();
		$this->dropDataColumn();
		$this->dropPicklistTable();
		$this->clearCaches();
	}

	public function safeDown(): void
	{
		echo "m260723_000001: safeDown not supported — restore DB backup.\n";
	}

	private function removeCustomViewReferences(): void
	{
		foreach (['vtiger_cvcolumnlist', 'vtiger_cvadvfilter', 'vtiger_cvstdfilter'] as $table) {
			if ($this->db->getTableSchema($table, true) === null) {
				continue;
			}
			$this->delete($table, ['columnname' => self::CV_COLUMN]);
		}
	}

	private function deleteFieldMetadata(): void
	{
		$module = Module::getInstance(self::MODULE_NAME);
		if ($module === false) {
			return;
		}
		$field = Field::getInstance(self::FIELD_NAME, $module);
		if ($field) {
			$field->delete();
		}
	}

	private function dropDataColumn(): void
	{
		$schema = $this->db->getSchema()->getTableSchema(self::TABLE, true);
		if ($schema !== null && isset($schema->columns[self::FIELD_NAME])) {
			$this->dropColumn(self::TABLE, self::FIELD_NAME);
		}
	}

	private function dropPicklistTable(): void
	{
		if ($this->db->getTableSchema(self::PICKLIST_TABLE, true) !== null) {
			$this->dropTable(self::PICKLIST_TABLE);
		}
		$picklist = $this->db->getTableSchema('vtiger_picklist', true);
		if ($picklist !== null) {
			$this->delete('vtiger_picklist', ['name' => self::FIELD_NAME]);
		}
	}

	private function clearCaches(): void
	{
		\App\Cache\Cache::delete('ModuleFields', (string) self::TABID);
		\App\Cache\Cache::delete('fieldInfo', (string) self::TABID);
		\App\Fields\Field::clearFieldsPermissionsCacheForTab(self::TABID);
		\App\Email\Mail::clearTemplateListCache();
		\App\Cache\Cache::clearNamespace('MailTempleteDetail');
	}
}
