<?php
/**
 * Add u_yf_emailtemplates.is_system — controls compose picker visibility (independent of sys_name).
 *
 * Run via: docker compose exec -T app php yii migrate --migrationPath=migrations/Users/ --interactive=0
 */

declare(strict_types=1);

use yii\db\Migration;
use yii\db\Query;

class m260703_000001_email_templates_is_system extends Migration
{
	private const EMAIL_TEMPLATES_TABID = 112;
	private const BASIC_BLOCK = 376;
	private const FIELD_IS_SYSTEM = 303433;

	/** @var list<string> */
	private const COMPOSE_MODULES = ['Candidates', 'ProjektyRekrutacyjne'];

	/** @var list<string> */
	private const SYSTEM_MODULES = [
		'Accounts',
		'Calendar',
		'Contacts',
		'Events',
		'HelpDesk',
		'ModComments',
		'Notification',
		'Users',
	];

	/** @var list<string> Konsultanci templates available in manual compose */
	private const KONSULTANCI_COMPOSE_SYS_NAMES = [
		'blank-mail-to-consultant-template',
	];

	public function safeUp(): void
	{
		$this->ensureColumn();
		$this->backfillIsSystem();
		$this->ensureFieldMetadata();
		$this->clearCaches();
	}

	public function safeDown(): void
	{
		echo "m260703_000001: safeDown not supported — restore DB backup.\n";
	}

	private function ensureColumn(): void
	{
		$schema = $this->db->schema->getTableSchema('u_yf_emailtemplates', true);
		if ($schema !== null && isset($schema->columns['is_system'])) {
			return;
		}

		$this->addColumn(
			'u_yf_emailtemplates',
			'is_system',
			$this->tinyInteger(1)->notNull()->defaultValue(0)->after('sys_name')
		);
	}

	private function backfillIsSystem(): void
	{
		$this->update('u_yf_emailtemplates', ['is_system' => 0]);

		$this->update(
			'u_yf_emailtemplates',
			['is_system' => 1],
			['module' => self::SYSTEM_MODULES]
		);

		$this->update(
			'u_yf_emailtemplates',
			['is_system' => 1],
			['and', ['module' => 'Konsultanci'], ['not in', 'sys_name', self::KONSULTANCI_COMPOSE_SYS_NAMES]]
		);

		$this->update(
			'u_yf_emailtemplates',
			['is_system' => 0],
			['module' => self::COMPOSE_MODULES]
		);
	}

	private function ensureFieldMetadata(): void
	{
		if ((new Query())->from('vtiger_field')->where(['fieldid' => self::FIELD_IS_SYSTEM])->exists()) {
			$this->update(
				'vtiger_field',
				[
					'tabid' => self::EMAIL_TEMPLATES_TABID,
					'columnname' => 'is_system',
					'tablename' => 'u_yf_emailtemplates',
					'fieldname' => 'is_system',
					'fieldlabel' => 'FL_IS_SYSTEM',
					'uitype' => 56,
					'displaytype' => 1,
					'typeofdata' => 'C~O',
					'block' => self::BASIC_BLOCK,
					'sequence' => 6,
				],
				['fieldid' => self::FIELD_IS_SYSTEM]
			);

			return;
		}

		$this->insert('vtiger_field', [
			'fieldid' => self::FIELD_IS_SYSTEM,
			'tabid' => self::EMAIL_TEMPLATES_TABID,
			'columnname' => 'is_system',
			'tablename' => 'u_yf_emailtemplates',
			'generatedtype' => 1,
			'uitype' => 56,
			'fieldname' => 'is_system',
			'fieldlabel' => 'FL_IS_SYSTEM',
			'readonly' => 1,
			'presence' => 2,
			'defaultvalue' => '0',
			'maximumlength' => 100,
			'sequence' => 6,
			'block' => self::BASIC_BLOCK,
			'displaytype' => 1,
			'typeofdata' => 'C~O',
			'quickcreate' => 1,
			'quickcreatesequence' => null,
			'info_type' => 'BAS',
			'masseditable' => 0,
			'helpinfo' => 'FL_IS_SYSTEM_HELPINFO',
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
				'fieldid' => self::FIELD_IS_SYSTEM,
			])->exists()) {
				continue;
			}
			$this->insert('vtiger_profile2field', [
				'profileid' => (int) $profileId,
				'tabid' => self::EMAIL_TEMPLATES_TABID,
				'fieldid' => self::FIELD_IS_SYSTEM,
				'visible' => 0,
				'readonly' => 0,
			]);
		}
	}

	private function clearCaches(): void
	{
		\App\Cache\Cache::delete('ModuleFields', (string) self::EMAIL_TEMPLATES_TABID);
		\App\Cache\Cache::delete('fieldInfo', (string) self::EMAIL_TEMPLATES_TABID);
		\App\Email\Mail::clearTemplateListCache();
	}
}
