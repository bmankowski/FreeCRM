<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * HelpDesk fields for GitHub issue sync (Report Issue widget).
 *
 * Run via: yii migrate --migrationPath=migrations/Users/
 */

declare(strict_types=1);

use yii\db\Migration;
use yii\db\Query;

class m260609_000009_report_issue_helpdesk_github extends Migration
{
	private const TABLE = 'vtiger_troubletickets';
	private const TABID = 13;
	private const FIELD_URL_ID = 303010;
	private const FIELD_NUMBER_ID = 303011;
	private const BLOCK_ID = 25;

	public function safeUp(): void
	{
		$schema = $this->db->getSchema()->getTableSchema(self::TABLE, true);
		if ($schema === null) {
			return;
		}

		if (!isset($schema->columns['github_issue_url'])) {
			$this->addColumn(self::TABLE, 'github_issue_url', $this->string(512)->null());
		}
		if (!isset($schema->columns['github_issue_number'])) {
			$this->addColumn(self::TABLE, 'github_issue_number', $this->integer()->null());
		}

		$this->ensureField([
			'fieldid' => self::FIELD_URL_ID,
			'columnname' => 'github_issue_url',
			'fieldname' => 'github_issue_url',
			'fieldlabel' => 'FL_GITHUB_ISSUE_URL',
			'uitype' => 17,
			'typeofdata' => 'V~O',
			'sequence' => 24,
		]);
		$this->ensureField([
			'fieldid' => self::FIELD_NUMBER_ID,
			'columnname' => 'github_issue_number',
			'fieldname' => 'github_issue_number',
			'fieldlabel' => 'FL_GITHUB_ISSUE_NUMBER',
			'uitype' => 7,
			'typeofdata' => 'I~O',
			'sequence' => 25,
		]);

		$this->syncFieldSeq(max(self::FIELD_URL_ID, self::FIELD_NUMBER_ID));
		$this->clearHelpDeskFieldCache();
	}

	public function safeDown(): void
	{
		foreach ([self::FIELD_URL_ID, self::FIELD_NUMBER_ID] as $fieldId) {
			$this->delete('vtiger_profile2field', ['fieldid' => $fieldId]);
			$this->delete('vtiger_field', ['fieldid' => $fieldId]);
		}

		$schema = $this->db->getSchema()->getTableSchema(self::TABLE, true);
		if ($schema !== null) {
			if (isset($schema->columns['github_issue_number'])) {
				$this->dropColumn(self::TABLE, 'github_issue_number');
			}
			if (isset($schema->columns['github_issue_url'])) {
				$this->dropColumn(self::TABLE, 'github_issue_url');
			}
		}

		$this->clearHelpDeskFieldCache();
	}

	private function ensureField(array $field): void
	{
		if ((new Query())->from('vtiger_field')->where(['fieldid' => $field['fieldid']])->exists()) {
			return;
		}

		$this->insert('vtiger_field', [
			'fieldid' => $field['fieldid'],
			'tabid' => self::TABID,
			'columnname' => $field['columnname'],
			'tablename' => self::TABLE,
			'generatedtype' => 1,
			'uitype' => $field['uitype'],
			'fieldname' => $field['fieldname'],
			'fieldlabel' => $field['fieldlabel'],
			'readonly' => 1,
			'presence' => 2,
			'defaultvalue' => '',
			'maximumlength' => 100,
			'sequence' => $field['sequence'],
			'block' => self::BLOCK_ID,
			'displaytype' => 2,
			'typeofdata' => $field['typeofdata'],
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
				'fieldid' => $field['fieldid'],
				'visible' => 0,
				'readonly' => 1,
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
