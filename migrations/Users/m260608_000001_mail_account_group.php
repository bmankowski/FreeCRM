<?php
/**
 * FreeCRM - Link shared mail accounts to CRM groups; recruitment compose uses personal + group only.
 *
 * Run via: docker compose exec -T app php yii migrate --migrationPath=migrations/Users/ --interactive=0
 */

declare(strict_types=1);

use yii\db\Migration;

class m260608_000001_mail_account_group extends Migration
{
	public function safeUp(): void
	{
		$schema = $this->db->getSchema()->getTableSchema('u_yf_mail_accounts', true);
		if ($schema !== null && !isset($schema->columns['group_id'])) {
			$this->addColumn('u_yf_mail_accounts', 'group_id', $this->integer()->null()->after('owner_user_id'));
			$this->createIndex('idx_mail_accounts_group', 'u_yf_mail_accounts', 'group_id');
		}

		$this->update(
			'u_yf_emailtemplates',
			['sender_type' => 'user_account'],
			[
				'and',
				['module' => ['Kandydaci', 'ProjektyRekrutacyjne']],
				['sys_name' => null],
			]
		);

		$this->seedRecruitmentGroupMailbox();
	}

	public function safeDown(): void
	{
		$this->update(
			'u_yf_emailtemplates',
			['sender_type' => 'any'],
			[
				'and',
				['module' => ['Kandydaci', 'ProjektyRekrutacyjne']],
				['sys_name' => null],
			]
		);

		$accountId = (new \App\Db\Query())
			->select('id')
			->from('u_yf_mail_accounts')
			->where(['kind' => 'group', 'username' => 'rekrutacja@itconnect.pl'])
			->scalar();
		if ($accountId) {
			$this->delete('u_yf_mail_account_users', ['account_id' => (int) $accountId]);
			$this->delete('u_yf_mail_accounts', ['id' => (int) $accountId]);
		}

		$schema = $this->db->getSchema()->getTableSchema('u_yf_mail_accounts', true);
		if ($schema !== null && isset($schema->columns['group_id'])) {
			$this->dropIndex('idx_mail_accounts_group', 'u_yf_mail_accounts');
			$this->dropColumn('u_yf_mail_accounts', 'group_id');
		}
	}

	private function seedRecruitmentGroupMailbox(): void
	{
		$groupId = (int) ((new \App\Db\Query())
			->select('groupid')
			->from('vtiger_groups')
			->where(['groupname' => 'Grupa rekrutacja'])
			->scalar() ?: 0);
		if ($groupId <= 0) {
			return;
		}

		$existingId = (int) ((new \App\Db\Query())
			->select('id')
			->from('u_yf_mail_accounts')
			->where(['kind' => 'group', 'group_id' => $groupId])
			->scalar() ?: 0);
		if ($existingId > 0) {
			\App\Modules\Mail\Models\Account::syncGroupMembers($existingId, $groupId);
			return;
		}

		$smtp = (new \App\Db\Query())
			->from('s_yf_mail_smtp')
			->where(['username' => 'rekrutacja@itconnect.pl'])
			->one();
		if (!$smtp) {
			return;
		}

		$encryption = new \App\Security\Encryption();
		if (!$encryption->isActive()) {
			return;
		}

		$password = (string) ($smtp['password'] ?? '');
		if ($password === '') {
			return;
		}

		$host = (string) ($smtp['host'] ?? 'itconnect.pl');
		\App\Modules\Mail\Models\Account::saveGroup([
			'name' => (string) ($smtp['name'] ?? 'Rekrutacja'),
			'group_id' => $groupId,
			'imap_host' => $host,
			'imap_port' => 993,
			'imap_secure' => 'ssl',
			'smtp_host' => $host,
			'smtp_port' => (int) ($smtp['port'] ?? 465),
			'smtp_secure' => (string) ($smtp['secure'] ?? 'ssl'),
			'username' => (string) ($smtp['username'] ?? ''),
			'password' => $password,
			'from_name' => (string) ($smtp['from_name'] ?? ''),
			'reply_to_mode' => 'same_as_from',
			'append_sent' => 1,
		], null, [], true);
	}
}
