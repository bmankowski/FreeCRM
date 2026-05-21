<?php
/**
 * FreeCRM - Display order for email templates (send-mail modal listbox).
 *
 * Run via: yii migrate --migrationPath=migrations/Users/
 */

declare(strict_types=1);

use yii\db\Migration;
use yii\db\Query;

class m260520_000003_emailtemplates_sequence extends Migration
{
	private const TABLE = 'u_yf_emailtemplates';
	private const TABID = 112;
	private const BLOCK_BASIC = 376;
	private const FIELD_ID = 303216;

	public function safeUp(): void
	{
		$schema = $this->db->getSchema()->getTableSchema(self::TABLE, true);
		if ($schema === null) {
			return;
		}

		if (!isset($schema->columns['sequence'])) {
			$this->addColumn(self::TABLE, 'sequence', $this->integer()->notNull()->defaultValue(0));
		}

		$this->backfillSequence();

		if ((new Query())->from('vtiger_field')->where(['tabid' => self::TABID, 'fieldname' => 'sequence'])->exists()) {
			$this->clearFieldMetadataCache();
			return;
		}

		$this->insert('vtiger_field', [
			'fieldid' => self::FIELD_ID,
			'tabid' => self::TABID,
			'columnname' => 'sequence',
			'tablename' => self::TABLE,
			'generatedtype' => 1,
			'uitype' => 7,
			'fieldname' => 'sequence',
			'fieldlabel' => 'LBL_SEQUENCE',
			'readonly' => 1,
			'presence' => 2,
			'defaultvalue' => '0',
			'maximumlength' => 100,
			'sequence' => 5,
			'block' => self::BLOCK_BASIC,
			'displaytype' => 1,
			'typeofdata' => 'I~O',
			'quickcreate' => 1,
			'quickcreatesequence' => 0,
			'info_type' => 'BAS',
			'masseditable' => 1,
			'helpinfo' => '',
			'summaryfield' => 0,
			'fieldparams' => '',
			'header_field' => null,
			'maxlengthtext' => 0,
			'maxwidthcolumn' => 0,
		]);

		$this->insert('vtiger_def_org_field', [
			'tabid' => self::TABID,
			'fieldid' => self::FIELD_ID,
			'visible' => 0,
			'readonly' => 0,
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

		$maxFieldId = (int) (new Query())->from('vtiger_field')->max('fieldid');
		if ($maxFieldId < self::FIELD_ID) {
			$maxFieldId = self::FIELD_ID;
		}
		$seqSchema = $this->db->getSchema()->getTableSchema('vtiger_field_seq', true);
		if ($seqSchema !== null) {
			$this->db->createCommand()->update('vtiger_field_seq', ['id' => $maxFieldId], 'id >= 0')->execute();
		}

		$this->clearFieldMetadataCache();
	}

	public function safeDown(): void
	{
		$this->delete('vtiger_profile2field', ['fieldid' => self::FIELD_ID]);
		$this->delete('vtiger_def_org_field', ['fieldid' => self::FIELD_ID]);
		$this->delete('vtiger_field', ['fieldid' => self::FIELD_ID]);

		$schema = $this->db->getSchema()->getTableSchema(self::TABLE, true);
		if ($schema !== null && isset($schema->columns['sequence'])) {
			$this->dropColumn(self::TABLE, 'sequence');
		}

		$this->clearFieldMetadataCache();
	}

	private function backfillSequence(): void
	{
		$rows = (new Query())
			->select(['emailtemplatesid', 'module'])
			->from(self::TABLE)
			->orderBy(['module' => SORT_ASC, 'emailtemplatesid' => SORT_ASC])
			->all($this->db);
		$perModule = [];
		foreach ($rows as $row) {
			$module = (string) ($row['module'] ?? '');
			if (!isset($perModule[$module])) {
				$perModule[$module] = 0;
			}
			$perModule[$module]++;
			$this->update(self::TABLE, ['sequence' => $perModule[$module]], [
				'emailtemplatesid' => (int) $row['emailtemplatesid'],
			]);
		}
	}

	private function clearFieldMetadataCache(): void
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
