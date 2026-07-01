<?php
/**
 * FreeCRM - HelpDesk clickable page-URL field for the Report Issue widget.
 *
 * Run via: yii migrate --migrationPath=migrations/Users/
 */

declare(strict_types=1);

use yii\db\Migration;
use yii\db\Query;

class m260629_000001_report_issue_page_url extends Migration
{
	private const TABLE = 'vtiger_troubletickets';
	private const TABID = 13;
	private const FIELD_ID = 303013;
	private const BLOCK_ID = 25;

	public function safeUp(): void
	{
		$schema = $this->db->getSchema()->getTableSchema(self::TABLE, true);
		if ($schema === null) {
			return;
		}

		if (!isset($schema->columns['report_issue_url'])) {
			$this->addColumn(self::TABLE, 'report_issue_url', $this->string(512)->null());
		}

		$this->ensureField();
		$this->syncFieldSeq(self::FIELD_ID);
		$this->clearHelpDeskFieldCache();
	}

	public function safeDown(): void
	{
		$this->delete('vtiger_profile2field', ['fieldid' => self::FIELD_ID]);
		$this->delete('vtiger_field', ['fieldid' => self::FIELD_ID]);

		$schema = $this->db->getSchema()->getTableSchema(self::TABLE, true);
		if ($schema !== null && isset($schema->columns['report_issue_url'])) {
			$this->dropColumn(self::TABLE, 'report_issue_url');
		}

		$this->clearHelpDeskFieldCache();
	}

	private function ensureField(): void
	{
		if ((new Query())->from('vtiger_field')->where(['fieldid' => self::FIELD_ID])->exists()) {
			return;
		}

		$this->insert('vtiger_field', [
			'fieldid' => self::FIELD_ID,
			'tabid' => self::TABID,
			'columnname' => 'report_issue_url',
			'tablename' => self::TABLE,
			'generatedtype' => 1,
			'uitype' => 17,
			'fieldname' => 'report_issue_url',
			'fieldlabel' => 'FL_REPORT_ISSUE_URL',
			'readonly' => 0,
			'presence' => 2,
			'defaultvalue' => '',
			'maximumlength' => 512,
			'sequence' => 27,
			'block' => self::BLOCK_ID,
			'displaytype' => 2,
			'typeofdata' => 'V~O',
			'quickcreate' => 1,
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
			->where(['tabid' => self::TABID])
			->column();
		foreach ($profileIds as $profileId) {
			$this->insert('vtiger_profile2field', [
				'profileid' => (int) $profileId,
				'tabid' => self::TABID,
				'fieldid' => self::FIELD_ID,
				'visible' => 0,
				'readonly' => 0,
			]);
		}
	}

	private function syncFieldSeq(int $fieldId): void
	{
		$maxFieldId = (int) (new Query())->from('vtiger_field')->max('fieldid');
		if ($maxFieldId < $fieldId) {
			$maxFieldId = $fieldId;
		}
		$seqSchema = $this->db->getSchema()->getTableSchema('vtiger_field_seq', true);
		if ($seqSchema !== null) {
			$this->db->createCommand()->update('vtiger_field_seq', ['id' => $maxFieldId], 'id >= 0')->execute();
		}
	}

	private function clearHelpDeskFieldCache(): void
	{
		if (!class_exists(\App\Cache\Cache::class)) {
			return;
		}
		\App\Cache\Cache::init();
		\App\Cache\Cache::delete('ModuleFields', self::TABID);
		\App\Cache\Cache::delete('fieldInfo', self::TABID);
		if (isset(\App\Utils\VTCacheUtils::$_fieldinfo_cache[self::TABID])) {
			unset(\App\Utils\VTCacheUtils::$_fieldinfo_cache[self::TABID]);
		}
		\App\Fields\Field::clearFieldsPermissionsCacheForTab(self::TABID);
	}
}
