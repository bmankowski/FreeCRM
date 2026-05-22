<?php
/**
 * FreeCRM - Email templates: SMTP server listbox (mailSmtpSelect) instead of priority picklist.
 *
 * Run via: yii migrate --migrationPath=migrations/Users/
 */

declare(strict_types=1);

use yii\db\Migration;
use yii\db\Query;

class m260522_000001_emailtemplates_smtp_select extends Migration
{
	private const TABLE = 'u_yf_emailtemplates';
	private const TABID = 112;
	private const FIELD_ID = 2483;
	private const UITYPE = 358;
	private const WS_FIELDTYPE_ID = 57;

	public function safeUp(): void
	{
		$schema = $this->db->getSchema()->getTableSchema(self::TABLE, true);
		if ($schema === null) {
			return;
		}

		if (!isset($schema->columns['smtp_id'])) {
			$this->addColumn(self::TABLE, 'smtp_id', $this->integer()->unsigned()->null());
		}

		if (isset($schema->columns['email_template_priority'])) {
			$this->db->createCommand(
				'UPDATE `' . self::TABLE . '` SET `smtp_id` = `email_template_priority`'
				. ' WHERE `email_template_priority` IS NOT NULL AND `email_template_priority` > 0'
				. ' AND (`smtp_id` IS NULL OR `smtp_id` = 0)'
			)->execute();
		}

		if (!(new Query())->from('vtiger_ws_fieldtype')->where(['uitype' => self::UITYPE])->exists()) {
			$this->insert('vtiger_ws_fieldtype', [
				'fieldtypeid' => self::WS_FIELDTYPE_ID,
				'uitype' => self::UITYPE,
				'fieldtype' => 'mailSmtpSelect',
			]);
		}

		$this->update('vtiger_field', [
			'fieldname' => 'smtp_id',
			'columnname' => 'smtp_id',
			'fieldlabel' => 'FL_SMTP',
			'uitype' => self::UITYPE,
			'typeofdata' => 'I~O',
		], [
			'fieldid' => self::FIELD_ID,
			'tabid' => self::TABID,
		]);

		$this->clearCaches();
	}

	public function safeDown(): void
	{
		$schema = $this->db->getSchema()->getTableSchema(self::TABLE, true);
		if ($schema !== null && isset($schema->columns['email_template_priority'])) {
			$this->db->createCommand(
				'UPDATE `' . self::TABLE . '` SET `email_template_priority` = `smtp_id`'
				. ' WHERE `smtp_id` IS NOT NULL AND `smtp_id` > 0'
			)->execute();
		}

		$this->update('vtiger_field', [
			'fieldname' => 'email_template_priority',
			'columnname' => 'email_template_priority',
			'fieldlabel' => 'FL_SMTP_PRIORITY',
			'uitype' => 16,
			'typeofdata' => 'V~O',
		], [
			'fieldid' => self::FIELD_ID,
			'tabid' => self::TABID,
		]);

		$this->delete('vtiger_ws_fieldtype', ['uitype' => self::UITYPE]);

		if ($schema !== null && isset($schema->columns['smtp_id'])) {
			$this->dropColumn(self::TABLE, 'smtp_id');
		}

		$this->clearCaches();
	}

	private function clearCaches(): void
	{
		if (!class_exists(\App\Cache\Cache::class)) {
			return;
		}
		\App\Cache\Cache::init();
		\App\Cache\Cache::delete('ModuleFields', self::TABID);
		\App\Cache\Cache::delete('fieldInfo', self::TABID);
		\App\Cache\Cache::delete('SmtpServers', 'all');
		\App\Cache\Cache::delete('DefaultSmtp', '');
		if (isset(\App\Utils\VTCacheUtils::$_fieldinfo_cache[self::TABID])) {
			unset(\App\Utils\VTCacheUtils::$_fieldinfo_cache[self::TABID]);
		}
		if (class_exists(\App\Fields\Field::class)) {
			\App\Fields\Field::clearFieldsPermissionsCacheForTab(self::TABID);
		}
	}
}
