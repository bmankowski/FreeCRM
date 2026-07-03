<?php
/**
 * EmailTemplates: separate footer field via vtlib\Block::addField().
 *
 * Run via: docker compose exec -T app php yii migrate --migrationPath=migrations/Users/ --interactive=0
 */

declare(strict_types=1);

use App\Modules\Base\Models\Field;
use App\Modules\Base\Models\Module;
use yii\db\Migration;
use yii\db\Query;

class m260703_000005_emailtemplates_footer_field extends Migration
{
	public $transaction = false;

	private const MODULE_NAME = 'EmailTemplates';
	private const TABID = 112;
	private const CONTENT_BLOCK = 377;
	private const TABLE = 'u_yf_emailtemplates';
	private const FIELD_NAME = 'footer';

	public function safeUp(): void
	{
		$module = Module::getInstance(self::MODULE_NAME);
		if ($module === false) {
			return;
		}

		if (Field::getInstance(self::FIELD_NAME, $module)) {
			$this->clearCaches();

			return;
		}

		$this->syncFieldSequence();

		$vtModule = \vtlib\Module::getInstance(self::MODULE_NAME);
		$block = \vtlib\Block::getInstance(self::CONTENT_BLOCK, $vtModule);
		if ($block === false) {
			throw new \RuntimeException('EmailTemplates content block not found: ' . self::CONTENT_BLOCK);
		}

		$field = new \stdClass();
		$field->name = self::FIELD_NAME;
		$field->label = 'FL_FOOTER';
		$field->table = self::TABLE;
		$field->column = self::FIELD_NAME;
		$field->columntype = 'text';
		$field->uitype = 300;
		$field->typeofdata = 'V';
		$field->displaytype = 1;
		$field->generatedtype = 1;
		$field->presence = 0;
		$field->sequence = 3;
		$field->quickcreate = 0;
		$field->masseditable = 0;

		$block->addField($field);
		$this->clearCaches();
	}

	public function safeDown(): void
	{
		$module = Module::getInstance(self::MODULE_NAME);
		if ($module === false) {
			return;
		}

		$field = Field::getInstance(self::FIELD_NAME, $module);
		if ($field) {
			$field->delete();
		}

		$schema = $this->db->getSchema()->getTableSchema(self::TABLE, true);
		if ($schema !== null && isset($schema->columns[self::FIELD_NAME])) {
			$this->dropColumn(self::TABLE, self::FIELD_NAME);
		}

		$this->clearCaches();
	}

	private function syncFieldSequence(): void
	{
		$maxFieldId = (int) (new Query())->from('vtiger_field')->max('fieldid');
		if ($maxFieldId <= 0) {
			return;
		}
		$currentSeq = (int) (new Query())->from('vtiger_field_seq')->scalar();
		if ($currentSeq >= $maxFieldId) {
			return;
		}
		$this->db->createCommand()->update('vtiger_field_seq', ['id' => $maxFieldId], 'id >= 0')->execute();
	}

	private function clearCaches(): void
	{
		\App\Cache\Cache::delete('ModuleFields', (string) self::TABID);
		\App\Cache\Cache::delete('fieldInfo', (string) self::TABID);
		\App\Email\Mail::clearTemplateListCache();
	}
}
