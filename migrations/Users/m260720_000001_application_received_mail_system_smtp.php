<?php
/**
 * FreeCRM - Application-received confirmation template sends via recruitment system SMTP.
 *
 * Run via: docker compose exec -T app php yii migrate --migrationPath=migrations/Users/ --interactive=0
 */

declare(strict_types=1);

use yii\db\Migration;
use yii\db\Query;

class m260720_000001_application_received_mail_system_smtp extends Migration
{
	private const SYS_NAME = 'kandydaci_potwierdzenie_otrzymania_aplikacji';

	/** @var int Rekrutacja IT CONNECT (rekrutacja@itconnect.pl) */
	private const RECRUITMENT_SMTP_ID = 8;

	public function safeUp(): void
	{
		if ($this->db->getTableSchema('u_yf_emailtemplates', true) === null) {
			return;
		}

		$id = (int) (new Query())
			->select(['emailtemplatesid'])
			->from('u_yf_emailtemplates')
			->where(['sys_name' => self::SYS_NAME])
			->scalar($this->db);

		if ($id <= 0) {
			echo "Skip: template " . self::SYS_NAME . " not found.\n";

			return;
		}

		$this->update('u_yf_emailtemplates', [
			'sender_type' => 'system_smtp',
			'smtp_id' => self::RECRUITMENT_SMTP_ID,
		], ['emailtemplatesid' => $id]);

		\App\Email\Mail::clearTemplateListCache();
		echo "Updated EmailTemplate " . self::SYS_NAME . " (id={$id}) → system_smtp smtp_id=" . self::RECRUITMENT_SMTP_ID . ".\n";
	}

	public function safeDown(): void
	{
		if ($this->db->getTableSchema('u_yf_emailtemplates', true) === null) {
			return;
		}

		$id = (int) (new Query())
			->select(['emailtemplatesid'])
			->from('u_yf_emailtemplates')
			->where(['sys_name' => self::SYS_NAME])
			->scalar($this->db);

		if ($id <= 0) {
			return;
		}

		$this->update('u_yf_emailtemplates', [
			'sender_type' => 'user_account',
		], ['emailtemplatesid' => $id]);

		\App\Email\Mail::clearTemplateListCache();
	}
}
