<?php
/**
 * FreeCRM - drop per-account signature_html and template skip_account_signature.
 *
 * Run via: docker compose exec -T app php yii migrate --migrationPath=migrations/Users/ --interactive=0
 */

declare(strict_types=1);

use yii\db\Migration;

class m260603_000003_drop_mail_signature_html extends Migration
{
	public function safeUp(): void
	{
		$accounts = $this->db->getSchema()->getTableSchema('u_yf_mail_accounts', true);
		if ($accounts !== null && isset($accounts->columns['signature_html'])) {
			$this->dropColumn('u_yf_mail_accounts', 'signature_html');
		}

		$templates = $this->db->getSchema()->getTableSchema('u_yf_emailtemplates', true);
		if ($templates !== null && isset($templates->columns['skip_account_signature'])) {
			$this->dropColumn('u_yf_emailtemplates', 'skip_account_signature');
		}
	}

	public function safeDown(): void
	{
		$accounts = $this->db->getSchema()->getTableSchema('u_yf_mail_accounts', true);
		if ($accounts !== null && !isset($accounts->columns['signature_html'])) {
			$this->addColumn('u_yf_mail_accounts', 'signature_html', $this->mediumText()->null());
		}

		$templates = $this->db->getSchema()->getTableSchema('u_yf_emailtemplates', true);
		if ($templates !== null && !isset($templates->columns['skip_account_signature'])) {
			$this->addColumn('u_yf_emailtemplates', 'skip_account_signature', $this->tinyInteger(1)->notNull()->defaultValue(0));
		}
	}
}
