<?php
/**
 * FreeCRM - Register storage_path and external_url on Documents so retrieve_entity_info loads them.
 *
 * Run via: docker compose exec -T app php yii migrate --migrationPath=migrations/Users/ --interactive=0
 */

declare(strict_types=1);

use yii\db\Migration;
use yii\db\Query;

class m260615_000002_documents_missing_field_metadata extends Migration
{
	private const DOCUMENTS_TABID = 8;
	private const FILE_BLOCK = 18;
	private const FIELD_STORAGE_PATH = 213;
	private const FIELD_EXTERNAL_URL = 865;

	public function safeUp(): void
	{
		$this->ensureField(
			self::FIELD_STORAGE_PATH,
			'storage_path',
			'storage_path',
			'FL_STORAGE_PATH',
			6,
			'V~O'
		);
		$this->ensureField(
			self::FIELD_EXTERNAL_URL,
			'external_url',
			'external_url',
			'FL_EXTERNAL_URL',
			7,
			'V~O'
		);
		$this->clearDocumentsFieldCache();
	}

	public function safeDown(): void
	{
		echo "m260615_000002_documents_missing_field_metadata: safeDown not supported — restore DB backup.\n";
	}

	private function ensureField(
		int $fieldId,
		string $fieldName,
		string $columnName,
		string $fieldLabel,
		int $sequence,
		string $typeofData
	): void {
		if ((new Query())->from('vtiger_field')->where(['fieldid' => $fieldId])->exists()) {
			$this->update(
				'vtiger_field',
				[
					'tabid' => self::DOCUMENTS_TABID,
					'columnname' => $columnName,
					'tablename' => 'vtiger_notes',
					'fieldname' => $fieldName,
					'fieldlabel' => $fieldLabel,
				],
				['fieldid' => $fieldId]
			);

			return;
		}

		$this->insert('vtiger_field', [
			'fieldid' => $fieldId,
			'tabid' => self::DOCUMENTS_TABID,
			'columnname' => $columnName,
			'tablename' => 'vtiger_notes',
			'generatedtype' => 1,
			'uitype' => 1,
			'fieldname' => $fieldName,
			'fieldlabel' => $fieldLabel,
			'readonly' => 1,
			'presence' => 2,
			'defaultvalue' => '',
			'maximumlength' => 100,
			'sequence' => $sequence,
			'block' => self::FILE_BLOCK,
			'displaytype' => 2,
			'typeofdata' => $typeofData,
			'quickcreate' => 3,
			'quickcreatesequence' => null,
			'info_type' => 'BAS',
			'masseditable' => 0,
			'helpinfo' => '',
			'summaryfield' => 0,
			'fieldparams' => '',
			'header_field' => null,
			'maxlengthtext' => 0,
			'maxwidthcolumn' => 0,
		]);

		$profileIds = (new Query())
			->select('profileid')
			->distinct()
			->from('vtiger_profile2field')
			->where(['tabid' => self::DOCUMENTS_TABID])
			->column();
		foreach ($profileIds as $profileId) {
			if ((new Query())->from('vtiger_profile2field')->where([
				'profileid' => (int) $profileId,
				'tabid' => self::DOCUMENTS_TABID,
				'fieldid' => $fieldId,
			])->exists()) {
				continue;
			}
			$this->insert('vtiger_profile2field', [
				'profileid' => (int) $profileId,
				'tabid' => self::DOCUMENTS_TABID,
				'fieldid' => $fieldId,
				'visible' => 0,
				'readonly' => 1,
			]);
		}

		$maxFieldId = (new Query())->from('vtiger_field')->max('fieldid');
		if ((int) $maxFieldId >= $fieldId) {
			$this->update('vtiger_field_seq', ['id' => (int) $maxFieldId]);
		}
	}

	private function clearDocumentsFieldCache(): void
	{
		if (!class_exists(\App\Cache\Cache::class)) {
			return;
		}
		\App\Cache\Cache::init();
		\App\Cache\Cache::delete('ModuleFields', self::DOCUMENTS_TABID);
		\App\Cache\Cache::delete('fieldInfo', self::DOCUMENTS_TABID);
		if (isset(\App\Utils\VTCacheUtils::$_fieldinfo_cache[self::DOCUMENTS_TABID])) {
			unset(\App\Utils\VTCacheUtils::$_fieldinfo_cache[self::DOCUMENTS_TABID]);
		}
		\App\Fields\Field::clearFieldsPermissionsCacheForTab(self::DOCUMENTS_TABID);
	}
}
