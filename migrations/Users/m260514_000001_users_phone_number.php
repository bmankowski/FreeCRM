<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * Adds optional phone number for each user (vtiger_users.phone_number).
 *
 * Run via: yii migrate --migrationPath=migrations/Users/
 */

declare(strict_types=1);

use yii\db\Migration;
use yii\db\Query;

class m260514_000001_users_phone_number extends Migration
{
	private const TABLE = 'vtiger_users';
	private const TABID = 29;
	private const FIELD_ID = 303002;

	public function safeUp(): void
	{
		$schema = $this->db->getSchema()->getTableSchema(self::TABLE, true);
		if ($schema === null) {
			return;
		}

		if (!isset($schema->columns['phone_number'])) {
			$this->addColumn(self::TABLE, 'phone_number', $this->string(50)->null());
		}

		if ((new Query())->from('vtiger_field')->where(['tabid' => self::TABID, 'fieldname' => 'phone_number'])->exists()) {
			$this->clearUsersFieldMetadataCache();
			return;
		}

		$this->insert('vtiger_field', [
			'fieldid' => self::FIELD_ID,
			'tabid' => self::TABID,
			'columnname' => 'phone_number',
			'tablename' => self::TABLE,
			'generatedtype' => 1,
			'uitype' => 11,
			'fieldname' => 'phone_number',
			'fieldlabel' => 'FL_PHONE_NUMBER',
			'readonly' => 1,
			'presence' => 0,
			'defaultvalue' => '',
			'maximumlength' => 50,
			'sequence' => 7,
			'block' => 77,
			'displaytype' => 1,
			'typeofdata' => 'V~O',
			'quickcreate' => 1,
			'quickcreatesequence' => null,
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

		$this->clearUsersFieldMetadataCache();
	}

	public function safeDown(): void
	{
		$this->delete('vtiger_profile2field', ['fieldid' => self::FIELD_ID]);
		$this->delete('vtiger_def_org_field', ['fieldid' => self::FIELD_ID]);
		$this->delete('vtiger_field', ['fieldid' => self::FIELD_ID]);

		$schema = $this->db->getSchema()->getTableSchema(self::TABLE, true);
		if ($schema !== null && isset($schema->columns['phone_number'])) {
			$this->dropColumn(self::TABLE, 'phone_number');
		}

		$this->clearUsersFieldMetadataCache();
	}

	private function clearUsersFieldMetadataCache(): void
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
