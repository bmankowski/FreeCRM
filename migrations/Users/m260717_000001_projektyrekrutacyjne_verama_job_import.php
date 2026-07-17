<?php
/**
 * ProjektyRekrutacyjne: Verama job import fields, picklist, cron.
 *
 * Run: docker compose exec -T app php yii migrate --migrationPath=migrations/Users/ --interactive=0
 */

declare(strict_types=1);

use App\Field\FieldKind;
use App\Field\StorageType;
use App\Modules\Base\Models\Field;
use App\Modules\Base\Models\Module;
use App\Modules\ProjektyRekrutacyjne\Cron\VeramaJobImportTask;
use yii\db\Migration;
use yii\db\Query;

class m260717_000001_projektyrekrutacyjne_verama_job_import extends Migration
{
	public $transaction = false;

	private const MODULE_NAME = 'ProjektyRekrutacyjne';
	private const TABID = 119;
	private const BLOCK_ADDITIONAL = 462;
	private const TABLE = 'u_yf_projektyrekrutacyjne';

	public function safeUp(): void
	{
		$module = Module::getInstance(self::MODULE_NAME);
		if ($module === false) {
			return;
		}

		$this->addVarcharField($module, 'job_source', 'PLL_JOB_SOURCE', 'varchar(32)', 20);
		$this->addVarcharField($module, 'external_job_id', 'PLL_EXTERNAL_JOB_ID', 'varchar(64)', 21);
		$this->ensureIndex();
		$this->ensurePicklistValue($module);
		$this->ensureCronTask();
		$this->clearCaches();
	}

	public function safeDown(): void
	{
		$db = \App\Db\Db::getInstance();
		$db->createCommand()
			->delete('vtiger_cron_task', ['handler_class' => VeramaJobImportTask::class])
			->execute();

		$module = Module::getInstance(self::MODULE_NAME);
		if ($module === false) {
			return;
		}

		foreach (['job_source', 'external_job_id'] as $fieldName) {
			$field = Field::getInstance($fieldName, $module);
			if ($field) {
				$field->delete();
			}
			$schema = $this->db->getSchema()->getTableSchema(self::TABLE, true);
			if ($schema !== null && isset($schema->columns[$fieldName])) {
				$this->dropColumn(self::TABLE, $fieldName);
			}
		}

		$this->clearCaches();
	}

	private function addVarcharField(Module $module, string $name, string $label, string $columntype, int $sequence): void
	{
		if (Field::getInstance($name, $module)) {
			$existing = Field::getInstance($name, $module);
			if ($existing !== false) {
				$this->backfillFieldMetadata((int) $existing->getId(), 1, 'V');
			}

			return;
		}

		$this->syncFieldSequence();

		$vtModule = \vtlib\Module::getInstance(self::MODULE_NAME);
		$block = \vtlib\Block::getInstance(self::BLOCK_ADDITIONAL, $vtModule);
		if ($block === false) {
			throw new \RuntimeException('ProjektyRekrutacyjne block not found: ' . self::BLOCK_ADDITIONAL);
		}

		$field = new \stdClass();
		$field->name = $name;
		$field->label = $label;
		$field->table = self::TABLE;
		$field->column = $name;
		$field->columntype = $columntype;
		$field->uitype = 1;
		$field->typeofdata = 'V~O';
		$field->displaytype = 1;
		$field->generatedtype = 1;
		$field->presence = 2;
		$field->sequence = $sequence;
		$field->quickcreate = 1;
		$field->masseditable = 0;

		$block->addField($field);
		$this->backfillFieldMetadata((int) $field->id, 1, 'V~O');
	}

	private function ensureIndex(): void
	{
		$schema = $this->db->getSchema()->getTableSchema(self::TABLE, true);
		if ($schema === null || !isset($schema->columns['job_source'], $schema->columns['external_job_id'])) {
			return;
		}

		$indexes = $this->db->createCommand('SHOW INDEX FROM ' . self::TABLE . " WHERE Key_name = 'projekty_verama_job_ext'")->queryAll();
		if ($indexes !== []) {
			return;
		}

		$this->createIndex(
			'projekty_verama_job_ext',
			self::TABLE,
			['job_source', 'external_job_id']
		);
	}

	private function ensurePicklistValue(Module $module): void
	{
		$field = Field::getInstance('zrodlo_pozyskania_projektu', $module);
		if ($field === false) {
			throw new \RuntimeException('Field zrodlo_pozyskania_projektu not found');
		}
		$field->setPicklistValues(['Verama www']);
	}

	private function ensureCronTask(): void
	{
		$exists = (new Query())
			->from('vtiger_cron_task')
			->where(['handler_class' => VeramaJobImportTask::class])
			->exists();
		if ($exists) {
			return;
		}

		\vtlib\Cron::registerClassTask(
			'LBL_SCHEDULED_VERAMA_JOB_IMPORT',
			VeramaJobImportTask::class,
			600,
			self::MODULE_NAME,
			\vtlib\Cron::STATUS_ENABLED,
			0,
			'Import Verama jobs from import/jobs/pending'
		);
	}

	private function backfillFieldMetadata(int $fieldId, int $uitype, string $typeofdata): void
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
			$updates['field_kind'] = FieldKind::resolve($uitype, '', $typeofdata, $webserviceByUitype);
		}
		if (isset($schema->columns['storage_type'])) {
			$updates['storage_type'] = StorageType::fromTypeofdata($typeofdata);
		}

		if ($updates !== []) {
			try {
				$this->update('vtiger_field', $updates, ['fieldid' => $fieldId]);
			} catch (\Throwable $e) {
				// MariaDB 1020: row changed since last read after Block::addField
				$message = $e->getMessage();
				if (!str_contains($message, '1020') && !str_contains($message, 'Record has changed')) {
					throw $e;
				}
			}
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
		\App\Cache\Cache::delete('field-' . $tabid, 'job_source');
		\App\Cache\Cache::delete('field-' . $tabid, 'external_job_id');
	}
}
