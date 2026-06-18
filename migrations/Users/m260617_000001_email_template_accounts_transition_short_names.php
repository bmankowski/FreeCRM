<?php
/**
 * FreeCRM - Email template accounts junction, sys_name backfill, transition mail short names.
 *
 * Run via: docker compose exec -T app php yii migrate --migrationPath=migrations/Users/ --interactive=0
 */

declare(strict_types=1);

use yii\db\Migration;
use yii\db\Query;

class m260617_000001_email_template_accounts_transition_short_names extends Migration
{
	private const EMAIL_TEMPLATES_TABID = 112;
	private const BASIC_BLOCK = 376;
	private const FIELD_SYS_NAME = 303429;
	private const RECRUITMENT_MODULE = 'ProjektyRekrutacyjne';

	public function safeUp(): void
	{
		$this->ensureAccountsJunctionTable();
		$this->backfillRecruitmentSysNames();
		$this->migrateTransitionMailMatrix();
		$this->ensureSysNameFieldMetadata();
		$this->clearFieldCache();
	}

	public function safeDown(): void
	{
		echo "m260617_000001: safeDown not supported — restore DB backup.\n";
	}

	private function ensureAccountsJunctionTable(): void
	{
		if ($this->db->schema->getTableSchema('u_yf_accounts_emailtemplates', true) !== null) {
			return;
		}

		$this->createTable('u_yf_accounts_emailtemplates', [
			'emailtemplatesid' => $this->integer()->notNull(),
			'accountid' => $this->integer()->notNull(),
		], 'ENGINE=InnoDB DEFAULT CHARSET=utf8');
		$this->addPrimaryKey(
			'pk_u_yf_accounts_emailtemplates',
			'u_yf_accounts_emailtemplates',
			['emailtemplatesid', 'accountid']
		);
		$this->createIndex(
			'idx_u_yf_accounts_emailtemplates_account',
			'u_yf_accounts_emailtemplates',
			'accountid'
		);
		$this->addForeignKey(
			'fk_ete_template',
			'u_yf_accounts_emailtemplates',
			'emailtemplatesid',
			'vtiger_crmentity',
			'crmid',
			'CASCADE',
			'CASCADE'
		);
		$this->addForeignKey(
			'fk_ete_account',
			'u_yf_accounts_emailtemplates',
			'accountid',
			'vtiger_crmentity',
			'crmid',
			'CASCADE',
			'CASCADE'
		);
	}

	private function backfillRecruitmentSysNames(): void
	{
		$rows = (new Query())
			->select(['emailtemplatesid', 'name', 'sys_name'])
			->from('u_yf_emailtemplates')
			->innerJoin('vtiger_crmentity', 'u_yf_emailtemplates.emailtemplatesid = vtiger_crmentity.crmid')
			->where([
				'vtiger_crmentity.deleted' => 0,
				'u_yf_emailtemplates.module' => self::RECRUITMENT_MODULE,
			])
			->all();

		$used = (new Query())
			->select(['sys_name'])
			->from('u_yf_emailtemplates')
			->where(['and', ['module' => self::RECRUITMENT_MODULE], ['not', ['sys_name' => null]], ['<>', 'sys_name', '']])
			->column();
		$usedSet = array_flip(array_map('strval', $used));

		foreach ($rows as $row) {
			$existing = trim((string) ($row['sys_name'] ?? ''));
			if ($existing !== '') {
				continue;
			}
			$id = (int) $row['emailtemplatesid'];
			$slug = $this->slugFromName((string) ($row['name'] ?? 'template'));
			if (isset($usedSet[$slug])) {
				$slug = substr($slug, 0, 40) . '_' . $id;
			}
			$usedSet[$slug] = true;
			$this->update('u_yf_emailtemplates', ['sys_name' => $slug], ['emailtemplatesid' => $id]);
		}
	}

	private function migrateTransitionMailMatrix(): void
	{
		$table = 'u_yf_recruitment_status_transition_mail';
		if ($this->db->schema->getTableSchema($table, true) === null) {
			return;
		}

		$schema = $this->db->schema->getTableSchema($table, true);
		if (!isset($schema->columns['short_name'])) {
			$this->addColumn($table, 'short_name', $this->string(50)->null()->after('to_status'));
		}

		$rows = (new Query())
			->select(['id', 'email_template_id'])
			->from($table)
			->where(['short_name' => null])
			->all();

		foreach ($rows as $row) {
			$templateId = (int) ($row['email_template_id'] ?? 0);
			if ($templateId <= 0) {
				$this->delete($table, ['id' => (int) $row['id']]);
				continue;
			}
			$sysName = (new Query())
				->select(['sys_name'])
				->from('u_yf_emailtemplates')
				->where(['emailtemplatesid' => $templateId])
				->scalar();
			$sysName = trim((string) $sysName);
			if ($sysName === '') {
				$this->delete($table, ['id' => (int) $row['id']]);
				continue;
			}
			$this->update($table, ['short_name' => $sysName], ['id' => (int) $row['id']]);
		}

		if (isset($schema->columns['email_template_id'])) {
			try {
				$this->dropIndex('u_yf_recruitment_status_transition_mail_from_to_tpl', $table);
			} catch (\Throwable) {
			}
			$this->dropColumn($table, 'email_template_id');
		}

		$this->delete($table, ['or', ['short_name' => null], ['short_name' => '']]);

		$this->alterColumn($table, 'short_name', $this->string(50)->notNull());

		try {
			$this->createIndex(
				'u_yf_recruitment_status_transition_mail_from_to_sn',
				$table,
				['from_status', 'to_status', 'short_name'],
				true
			);
		} catch (\Throwable) {
		}
	}

	private function ensureSysNameFieldMetadata(): void
	{
		if ((new Query())->from('vtiger_field')->where(['fieldid' => self::FIELD_SYS_NAME])->exists()) {
			$this->update(
				'vtiger_field',
				[
					'tabid' => self::EMAIL_TEMPLATES_TABID,
					'columnname' => 'sys_name',
					'tablename' => 'u_yf_emailtemplates',
					'fieldname' => 'sys_name',
					'fieldlabel' => 'FL_SHORT_NAME',
					'uitype' => 1,
					'displaytype' => 1,
					'typeofdata' => 'V~O',
					'block' => self::BASIC_BLOCK,
					'sequence' => 5,
				],
				['fieldid' => self::FIELD_SYS_NAME]
			);

			return;
		}

		$this->insert('vtiger_field', [
			'fieldid' => self::FIELD_SYS_NAME,
			'tabid' => self::EMAIL_TEMPLATES_TABID,
			'columnname' => 'sys_name',
			'tablename' => 'u_yf_emailtemplates',
			'generatedtype' => 1,
			'uitype' => 1,
			'fieldname' => 'sys_name',
			'fieldlabel' => 'FL_SHORT_NAME',
			'readonly' => 1,
			'presence' => 2,
			'defaultvalue' => '',
			'maximumlength' => 50,
			'sequence' => 5,
			'block' => self::BASIC_BLOCK,
			'displaytype' => 1,
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
			->where(['tabid' => self::EMAIL_TEMPLATES_TABID])
			->column();
		foreach ($profileIds as $profileId) {
			if ((new Query())->from('vtiger_profile2field')->where([
				'profileid' => (int) $profileId,
				'tabid' => self::EMAIL_TEMPLATES_TABID,
				'fieldid' => self::FIELD_SYS_NAME,
			])->exists()) {
				continue;
			}
			$this->insert('vtiger_profile2field', [
				'profileid' => (int) $profileId,
				'tabid' => self::EMAIL_TEMPLATES_TABID,
				'fieldid' => self::FIELD_SYS_NAME,
				'visible' => 0,
				'readonly' => 0,
			]);
		}
	}

	private function slugFromName(string $name): string
	{
		$slug = mb_strtolower($name, 'UTF-8');
		$translit = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $slug);
		if ($translit !== false) {
			$slug = strtolower($translit);
		}
		$slug = preg_replace('/[^a-z0-9]+/u', '_', $slug) ?? '';
		$slug = trim($slug, '_');
		if ($slug === '') {
			$slug = 'template';
		}

		return substr($slug, 0, 50);
	}

	private function clearFieldCache(): void
	{
		\App\Cache\Cache::delete('ModuleFields', (string) self::EMAIL_TEMPLATES_TABID);
		\App\Cache\Cache::delete('fieldInfo', (string) self::EMAIL_TEMPLATES_TABID);
	}
}
