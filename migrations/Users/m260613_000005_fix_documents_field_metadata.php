<?php
/**
 * FreeCRM - Correct Documents field metadata after wrong field IDs in m260613_000003.
 *
 * Run via: docker compose exec -T app php yii migrate --migrationPath=migrations/Users/ --interactive=0
 */

declare(strict_types=1);

use yii\db\Migration;

class m260613_000005_fix_documents_field_metadata extends Migration
{
	private const DOCUMENTS_TABID = 8;

	public function safeUp(): void
	{
		$this->restoreAssignedToField();
		$this->restoreNoteContentField();
		$this->renameFileTypeField();
		$this->renameFileSizeField();
		$this->clearDocumentsFieldCache();
	}

	public function safeDown(): void
	{
		echo "m260613_000005_fix_documents_field_metadata: safeDown not supported — restore DB backup.\n";
	}

	private function restoreAssignedToField(): void
	{
		$this->update(
			'vtiger_field',
			[
				'fieldname' => 'assigned_user_id',
				'columnname' => 'smownerid',
				'tablename' => 'vtiger_crmentity',
			],
			['fieldid' => 208, 'tabid' => self::DOCUMENTS_TABID]
		);
	}

	private function restoreNoteContentField(): void
	{
		$this->update(
			'vtiger_field',
			[
				'fieldname' => 'notecontent',
				'columnname' => 'notecontent',
				'tablename' => 'vtiger_notes',
			],
			['fieldid' => 209, 'tabid' => self::DOCUMENTS_TABID]
		);
	}

	private function renameFileTypeField(): void
	{
		$this->update(
			'vtiger_field',
			[
				'fieldname' => 'mime_type',
				'columnname' => 'mime_type',
				'tablename' => 'vtiger_notes',
			],
			['fieldid' => 210, 'tabid' => self::DOCUMENTS_TABID]
		);
	}

	private function renameFileSizeField(): void
	{
		$this->update(
			'vtiger_field',
			[
				'fieldname' => 'size_bytes',
				'columnname' => 'size_bytes',
				'tablename' => 'vtiger_notes',
			],
			['fieldid' => 211, 'tabid' => self::DOCUMENTS_TABID]
		);
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
