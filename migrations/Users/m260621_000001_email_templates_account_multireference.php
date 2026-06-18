<?php
/**
 * FreeCRM - EmailTemplates account_id: uitype 10 → 306 MultiReference (comma-separated account IDs).
 *
 * Run via: docker compose exec -T app php yii migrate --migrationPath=migrations/Users/ --interactive=0
 */

declare(strict_types=1);

use yii\db\Migration;
use yii\db\Query;

class m260621_000001_email_templates_account_multireference extends Migration
{
	private const EMAIL_TEMPLATES_TABID = 112;
	private const FIELD_ACCOUNT_ID = 303430;

	public function safeUp(): void
	{
		$this->convertAccountIdColumn();
		$this->updateFieldMetadata();
		$this->clearFieldCache();
	}

	public function safeDown(): void
	{
		echo "m260621_000001: safeDown not supported — restore DB backup.\n";
	}

	private function convertAccountIdColumn(): void
	{
		$schema = $this->db->schema->getTableSchema('u_yf_emailtemplates', true);
		if ($schema === null || !isset($schema->columns['account_id'])) {
			return;
		}

		$rows = (new Query())
			->select(['emailtemplatesid', 'account_id'])
			->from('u_yf_emailtemplates')
			->where(['not', ['account_id' => null]])
			->andWhere(['<>', 'account_id', 0])
			->all();

		try {
			$this->dropIndex('idx_u_yf_emailtemplates_account_id', 'u_yf_emailtemplates');
		} catch (\Throwable) {
		}

		$this->alterColumn('u_yf_emailtemplates', 'account_id', $this->text()->null());

		foreach ($rows as $row) {
			$templateId = (int) ($row['emailtemplatesid'] ?? 0);
			$accountId = (int) ($row['account_id'] ?? 0);
			if ($templateId <= 0 || $accountId <= 0) {
				continue;
			}
			$this->update('u_yf_emailtemplates', ['account_id' => (string) $accountId], ['emailtemplatesid' => $templateId]);
		}
	}

	private function updateFieldMetadata(): void
	{
		if (!(new Query())->from('vtiger_field')->where(['fieldid' => self::FIELD_ACCOUNT_ID])->exists()) {
			return;
		}

		$this->update('vtiger_field', [
			'uitype' => 306,
			'typeofdata' => 'V~O',
		], ['fieldid' => self::FIELD_ACCOUNT_ID]);

		if (!(new Query())->from('vtiger_fieldmodulerel')->where(['fieldid' => self::FIELD_ACCOUNT_ID])->exists()) {
			$this->insert('vtiger_fieldmodulerel', [
				'fieldid' => self::FIELD_ACCOUNT_ID,
				'module' => 'EmailTemplates',
				'relmodule' => 'Accounts',
				'status' => null,
				'sequence' => 0,
			]);
		}
	}

	private function clearFieldCache(): void
	{
		\App\Cache\Cache::delete('ModuleFields', (string) self::EMAIL_TEMPLATES_TABID);
		\App\Cache\Cache::delete('fieldInfo', (string) self::EMAIL_TEMPLATES_TABID);
	}
}
