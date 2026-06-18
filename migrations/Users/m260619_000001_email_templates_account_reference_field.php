<?php
/**
 * FreeCRM - EmailTemplates account as uitype-10 reference (replaces junction table).
 *
 * Run via: docker compose exec -T app php yii migrate --migrationPath=migrations/Users/ --interactive=0
 */

declare(strict_types=1);

use yii\db\Migration;
use yii\db\Query;

class m260619_000001_email_templates_account_reference_field extends Migration
{
	private const EMAIL_TEMPLATES_TABID = 112;
	private const BASIC_BLOCK = 376;
	private const FIELD_ACCOUNT_ID = 303430;
	private const JUNCTION_TABLE = 'u_yf_accounts_emailtemplates';

	public function safeUp(): void
	{
		$this->ensureAccountIdColumn();
		$this->migrateJunctionToColumn();
		$this->ensureAccountFieldMetadata();
		$this->dropJunctionTable();
		$this->clearFieldCache();
	}

	public function safeDown(): void
	{
		echo "m260619_000001: safeDown not supported — restore DB backup.\n";
	}

	private function ensureAccountIdColumn(): void
	{
		$schema = $this->db->schema->getTableSchema('u_yf_emailtemplates', true);
		if ($schema === null || isset($schema->columns['account_id'])) {
			return;
		}

		$this->addColumn('u_yf_emailtemplates', 'account_id', $this->integer()->null());
		$this->createIndex('idx_u_yf_emailtemplates_account_id', 'u_yf_emailtemplates', 'account_id');
	}

	private function migrateJunctionToColumn(): void
	{
		if ($this->db->schema->getTableSchema(self::JUNCTION_TABLE, true) === null) {
			return;
		}

		$rows = (new Query())
			->select(['emailtemplatesid', 'accountid'])
			->from(self::JUNCTION_TABLE)
			->all();

		$byTemplate = [];
		foreach ($rows as $row) {
			$templateId = (int) ($row['emailtemplatesid'] ?? 0);
			$accountId = (int) ($row['accountid'] ?? 0);
			if ($templateId <= 0 || $accountId <= 0) {
				continue;
			}
			$byTemplate[$templateId][] = $accountId;
		}

		foreach ($byTemplate as $templateId => $accountIds) {
			$accountIds = array_values(array_unique($accountIds));
			if ($accountIds === []) {
				continue;
			}
			if (count($accountIds) > 1) {
				echo "WARN: template {$templateId} had " . count($accountIds)
					. " junction accounts — kept account_id={$accountIds[0]} only\n";
			}
			$this->update('u_yf_emailtemplates', ['account_id' => $accountIds[0]], ['emailtemplatesid' => $templateId]);
		}
	}

	private function ensureAccountFieldMetadata(): void
	{
		if ((new Query())->from('vtiger_field')->where(['fieldid' => self::FIELD_ACCOUNT_ID])->exists()) {
			$this->update(
				'vtiger_field',
				[
					'tabid' => self::EMAIL_TEMPLATES_TABID,
					'columnname' => 'account_id',
					'tablename' => 'u_yf_emailtemplates',
					'fieldname' => 'account_id',
					'fieldlabel' => 'FL_ACCOUNT',
					'uitype' => 10,
					'displaytype' => 1,
					'typeofdata' => 'I~O',
					'block' => self::BASIC_BLOCK,
					'sequence' => 6,
				],
				['fieldid' => self::FIELD_ACCOUNT_ID]
			);
		} else {
			$this->insert('vtiger_field', [
				'fieldid' => self::FIELD_ACCOUNT_ID,
				'tabid' => self::EMAIL_TEMPLATES_TABID,
				'columnname' => 'account_id',
				'tablename' => 'u_yf_emailtemplates',
				'generatedtype' => 1,
				'uitype' => 10,
				'fieldname' => 'account_id',
				'fieldlabel' => 'FL_ACCOUNT',
				'readonly' => 1,
				'presence' => 2,
				'defaultvalue' => '',
				'maximumlength' => 100,
				'sequence' => 6,
				'block' => self::BASIC_BLOCK,
				'displaytype' => 1,
				'typeofdata' => 'I~O',
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
		}

		if (!(new Query())->from('vtiger_fieldmodulerel')->where(['fieldid' => self::FIELD_ACCOUNT_ID])->exists()) {
			$this->insert('vtiger_fieldmodulerel', [
				'fieldid' => self::FIELD_ACCOUNT_ID,
				'module' => 'EmailTemplates',
				'relmodule' => 'Accounts',
				'status' => null,
				'sequence' => 0,
			]);
		}

		$profileIds = (new Query())
			->select('profileid')
			->distinct()
			->from('vtiger_profile2field')
			->where(['tabid' => self::EMAIL_TEMPLATES_TABID])
			->column();
		foreach ($profileIds as $profileId) {
			if ((new Query())->from('vtiger_profile2field')->where([
				'profileid' => (int) $profileId,
				'tabid' => self::EMAIL_TEMPLATES_TABID,
				'fieldid' => self::FIELD_ACCOUNT_ID,
			])->exists()) {
				continue;
			}
			$this->insert('vtiger_profile2field', [
				'profileid' => (int) $profileId,
				'tabid' => self::EMAIL_TEMPLATES_TABID,
				'fieldid' => self::FIELD_ACCOUNT_ID,
				'visible' => 0,
				'readonly' => 0,
			]);
		}
	}

	private function dropJunctionTable(): void
	{
		if ($this->db->schema->getTableSchema(self::JUNCTION_TABLE, true) === null) {
			return;
		}

		try {
			$this->dropForeignKey('fk_ete_template', self::JUNCTION_TABLE);
		} catch (\Throwable) {
		}
		try {
			$this->dropForeignKey('fk_ete_account', self::JUNCTION_TABLE);
		} catch (\Throwable) {
		}
		$this->dropTable(self::JUNCTION_TABLE);
	}

	private function clearFieldCache(): void
	{
		\App\Cache\Cache::delete('ModuleFields', (string) self::EMAIL_TEMPLATES_TABID);
		\App\Cache\Cache::delete('fieldInfo', (string) self::EMAIL_TEMPLATES_TABID);
	}
}
