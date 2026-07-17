<?php
/**
 * ProjektyRekrutacyjne: cv_boolean_query — boolean CV search expression for kanban candidate picker.
 *
 * Run via: docker compose exec -T app php yii migrate --migrationPath=migrations/Users/ --interactive=0
 */

declare(strict_types=1);

use App\Field\FieldKind;
use App\Field\StorageType;
use App\Modules\Base\Models\Field;
use App\Modules\Base\Models\Module;
use yii\db\Migration;
use yii\db\Query;

class m260715_000001_projektyrekrutacyjne_cv_boolean_query extends Migration
{
	public $transaction = false;

	private const MODULE_NAME = 'ProjektyRekrutacyjne';
	private const TABID = 119;
	private const SKILLS_BLOCK = 460;
	private const TABLE = 'u_yf_projektyrekrutacyjne';
	private const FIELD_NAME = 'cv_boolean_query';

	public function safeUp(): void
	{
		$module = Module::getInstance(self::MODULE_NAME);
		if ($module === false) {
			return;
		}

		if (Field::getInstance(self::FIELD_NAME, $module)) {
			$existing = Field::getInstance(self::FIELD_NAME, $module);
			if ($existing !== false) {
				$this->backfillFieldMetadata((int) $existing->getId());
			}
			$this->clearCaches();

			return;
		}

		$this->syncFieldSequence();

		$vtModule = \vtlib\Module::getInstance(self::MODULE_NAME);
		$block = \vtlib\Block::getInstance(self::SKILLS_BLOCK, $vtModule);
		if ($block === false) {
			throw new \RuntimeException('ProjektyRekrutacyjne skills block not found: ' . self::SKILLS_BLOCK);
		}

		$field = new \stdClass();
		$field->name = self::FIELD_NAME;
		$field->label = 'PLL_CV_BOOLEAN_QUERY';
		$field->table = self::TABLE;
		$field->column = self::FIELD_NAME;
		$field->columntype = 'text';
		$field->uitype = 19;
		$field->typeofdata = 'V';
		$field->displaytype = 1;
		$field->generatedtype = 1;
		$field->presence = 2;
		$field->sequence = 14;
		$field->quickcreate = 1;
		$field->masseditable = 0;

		$block->addField($field);
		$this->backfillFieldMetadata((int) $field->id);
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

	private function backfillFieldMetadata(int $fieldId): void
	{
		if ($fieldId <= 0) {
			return;
		}

		$schema = $this->db->getTableSchema('vtiger_field', true);
		if ($schema === null) {
			return;
		}

		$webserviceByUitype = [];
		foreach ((new Query())->select(['uitype', 'fieldtype'])->from('vtiger_ws_fieldtype')->all() as $wsRow) {
			$webserviceByUitype[(int) $wsRow['uitype']] = (string) $wsRow['fieldtype'];
		}

		$updates = [];
		if (isset($schema->columns['field_kind'])) {
			$updates['field_kind'] = FieldKind::resolve(19, self::FIELD_NAME, 'V', $webserviceByUitype);
		}
		if (isset($schema->columns['storage_type'])) {
			$updates['storage_type'] = StorageType::fromTypeofdata('V');
		}

		if ($updates !== []) {
			$this->update('vtiger_field', $updates, ['fieldid' => $fieldId]);
		}
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
		$tabid = (string) self::TABID;
		\App\Cache\Cache::delete('ModuleFields', $tabid);
		\App\Cache\Cache::delete('fieldInfo', $tabid);
		\App\Cache\Cache::delete('field-' . $tabid, self::FIELD_NAME);
	}
}
