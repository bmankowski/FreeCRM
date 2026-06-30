<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * HelpDesk ticket roles: developer_id, business_id, assigned_user_id relabel.
 *
 * Run via: yii migrate --migrationPath=migrations/Users/
 */

declare(strict_types=1);

use yii\db\Migration;
use yii\db\Query;

class m260630_000001_helpdesk_ticket_roles extends Migration
{
	private const TABLE = 'vtiger_troubletickets';
	private const TABID = 13;
	private const BLOCK_ID = 25;
	private const FIELD_DEVELOPER_ID = 303431;
	private const FIELD_BUSINESS_ID = 303432;
	private const FIELD_ASSIGNED_ID = 156;
	private const EVENT_HANDLER_ID = 60;
	private const EVENT_HANDLER_CLASS = 'HelpDesk_TicketRoles_Handler';

	public function safeUp(): void
	{
		$schema = $this->db->getSchema()->getTableSchema(self::TABLE, true);
		if ($schema === null) {
			return;
		}

		if (!isset($schema->columns['developer_id'])) {
			$this->addColumn(self::TABLE, 'developer_id', $this->integer()->null());
		}
		if (!isset($schema->columns['business_id'])) {
			$this->addColumn(self::TABLE, 'business_id', $this->integer()->null());
		}

		$this->ensureEditableField([
			'fieldid' => self::FIELD_DEVELOPER_ID,
			'columnname' => 'developer_id',
			'fieldname' => 'developer_id',
			'fieldlabel' => 'FL_TICKET_DEVELOPER',
			'uitype' => 53,
			'typeofdata' => 'V~O',
			'sequence' => 28,
		]);
		$this->ensureEditableField([
			'fieldid' => self::FIELD_BUSINESS_ID,
			'columnname' => 'business_id',
			'fieldname' => 'business_id',
			'fieldlabel' => 'FL_TICKET_BUSINESS',
			'uitype' => 53,
			'typeofdata' => 'V~O',
			'sequence' => 29,
		]);

		$this->db->createCommand()->update(
			'vtiger_field',
			['fieldlabel' => 'FL_CURRENT_ASSIGNEE'],
			['fieldid' => self::FIELD_ASSIGNED_ID]
		)->execute();

		$this->db->createCommand(
			'UPDATE ' . self::TABLE . ' tt
			INNER JOIN vtiger_crmentity ce ON ce.crmid = tt.ticketid
			SET tt.business_id = ce.smcreatorid,
				tt.developer_id = ce.smownerid
			WHERE ce.deleted = 0
				AND (tt.business_id IS NULL OR tt.business_id = 0)
				AND (tt.developer_id IS NULL OR tt.developer_id = 0)'
		)->execute();

		if (!(new Query())->from('vtiger_eventhandlers')->where(['handler_class' => self::EVENT_HANDLER_CLASS])->exists()) {
			$this->insert('vtiger_eventhandlers', [
				'eventhandler_id' => self::EVENT_HANDLER_ID,
				'event_name' => 'EntityBeforeSave',
				'handler_class' => self::EVENT_HANDLER_CLASS,
				'is_active' => 1,
				'include_modules' => 'HelpDesk',
				'exclude_modules' => '',
				'priority' => 4,
				'owner_id' => self::TABID,
			]);
		}

		$this->syncFieldSeq(max(self::FIELD_DEVELOPER_ID, self::FIELD_BUSINESS_ID));
		$this->clearHelpDeskFieldCache();
	}

	public function safeDown(): void
	{
		$this->delete('vtiger_eventhandlers', ['handler_class' => self::EVENT_HANDLER_CLASS]);

		foreach ([self::FIELD_DEVELOPER_ID, self::FIELD_BUSINESS_ID] as $fieldId) {
			$this->delete('vtiger_profile2field', ['fieldid' => $fieldId]);
			$this->delete('vtiger_field', ['fieldid' => $fieldId]);
		}

		$this->db->createCommand()->update(
			'vtiger_field',
			['fieldlabel' => 'Assigned To'],
			['fieldid' => self::FIELD_ASSIGNED_ID]
		)->execute();

		$schema = $this->db->getSchema()->getTableSchema(self::TABLE, true);
		if ($schema !== null) {
			if (isset($schema->columns['business_id'])) {
				$this->dropColumn(self::TABLE, 'business_id');
			}
			if (isset($schema->columns['developer_id'])) {
				$this->dropColumn(self::TABLE, 'developer_id');
			}
		}

		$this->clearHelpDeskFieldCache();
	}

	private function ensureEditableField(array $field): void
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
			'displaytype' => 1,
			'typeofdata' => $field['typeofdata'],
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
