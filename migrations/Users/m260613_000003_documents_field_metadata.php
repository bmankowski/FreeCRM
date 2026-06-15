<?php
/**
 * FreeCRM - Documents field metadata updates.
 *
 * Run via: docker compose exec -T app php yii migrate --migrationPath=migrations/Users/ --interactive=0
 */

declare(strict_types=1);

use yii\db\Migration;
use yii\db\Query;

class m260613_000003_documents_field_metadata extends Migration
{
	private const DOCUMENTS_TABID = 8;

	private const FIELD_RENAMES = [
		207 => ['columnname' => 'original_name', 'fieldname' => 'original_name', 'tablename' => 'vtiger_notes'],
		210 => ['columnname' => 'mime_type', 'fieldname' => 'mime_type', 'tablename' => 'vtiger_notes'],
		211 => ['columnname' => 'size_bytes', 'fieldname' => 'size_bytes', 'tablename' => 'vtiger_notes'],
		212 => ['columnname' => 'location_type', 'fieldname' => 'location_type', 'tablename' => 'vtiger_notes'],
		214 => ['columnname' => 'active', 'fieldname' => 'active', 'tablename' => 'vtiger_notes'],
		215 => ['columnname' => 'download_count', 'fieldname' => 'download_count', 'tablename' => 'vtiger_notes'],
	];

	private const REMOVE_FIELD_IDS = [213, 865];

	public function safeUp(): void
	{
		foreach (self::FIELD_RENAMES as $fieldId => $values) {
			$this->update('vtiger_field', $values, ['fieldid' => $fieldId, 'tabid' => self::DOCUMENTS_TABID]);
		}

		foreach (self::REMOVE_FIELD_IDS as $fieldId) {
			$this->delete('vtiger_profile2field', ['fieldid' => $fieldId]);
			$this->delete('vtiger_field', ['fieldid' => $fieldId]);
		}

		$this->delete('vtiger_ws_entity_fieldtype', ['table_name' => 'vtiger_attachmentsfolder']);
		$this->delete('vtiger_ws_entity_name', ['table_name' => 'vtiger_attachmentsfolder']);
		$this->delete('vtiger_ws_entity_tables', ['table_name' => 'vtiger_attachmentsfolder']);

		$this->clearDocumentsFieldCache();
	}

	public function safeDown(): void
	{
		echo "m260613_000003_documents_field_metadata: safeDown not supported — restore DB backup.\n";
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
