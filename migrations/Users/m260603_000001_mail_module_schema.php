<?php
/**
 * FreeCRM - Mail module schema, metadata, cron, related lists.
 *
 * Run via: docker compose exec -T app php yii migrate --migrationPath=migrations/Users/ --interactive=0
 */

declare(strict_types=1);

use yii\db\Migration;
use yii\db\Query;

class m260603_000001_mail_module_schema extends Migration
{
	private const MAIL_TABID = 130;
	private const MAIL_TAB_NAME = 'Mail';

	private const RELATED_MODULES = [
		121 => 'Kandydaci',
		7 => 'Leads',
		6 => 'Accounts',
		4 => 'Contacts',
		13 => 'HelpDesk',
		86 => 'SSalesProcesses',
	];

	public function safeUp(): void
	{
		$this->createMailTables();
		$this->alterEmailTemplates();
		$this->registerMailTab();
		$this->registerCronTasks();
		$this->registerSettingsField();
		$this->registerRelatedLists();
	}

	public function safeDown(): void
	{
		$this->removeRelatedLists();
		$this->db->createCommand()->delete('vtiger_settings_field', ['name' => 'LBL_MAIL_ACCOUNTS'])->execute();
		$this->db->createCommand()->delete('vtiger_cron_task', ['module' => self::MAIL_TAB_NAME])->execute();
		$this->db->createCommand()->delete('vtiger_tab', ['name' => self::MAIL_TAB_NAME])->execute();

		$schema = $this->db->getSchema()->getTableSchema('u_yf_emailtemplates', true);
		if ($schema !== null) {
			if (isset($schema->columns['sender_type'])) {
				$this->dropColumn('u_yf_emailtemplates', 'sender_type');
			}
		}

		$this->dropTable('u_yf_mail_log');
		$this->dropTable('u_yf_mail_record_links');
		$this->dropTable('u_yf_mail_attachments');
		$this->dropTable('u_yf_mail_messages');
		$this->dropTable('u_yf_mail_account_users');
		$this->dropTable('u_yf_mail_accounts');
	}

	private function createMailTables(): void
	{
		if ($this->db->getSchema()->getTableSchema('u_yf_mail_accounts', true) === null) {
			$this->execute(<<<'SQL'
CREATE TABLE u_yf_mail_accounts (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(120) NOT NULL,
  kind ENUM('personal','shared') NOT NULL,
  owner_user_id INT NULL,
  imap_host VARCHAR(190) NOT NULL,
  imap_port SMALLINT UNSIGNED NOT NULL DEFAULT 993,
  imap_secure ENUM('ssl','tls','none') NOT NULL DEFAULT 'ssl',
  imap_validate_cert TINYINT(1) NOT NULL DEFAULT 1,
  imap_folder_inbox VARCHAR(190) NOT NULL DEFAULT 'INBOX',
  imap_folder_sent VARCHAR(190) NULL,
  smtp_host VARCHAR(190) NOT NULL,
  smtp_port SMALLINT UNSIGNED NOT NULL DEFAULT 465,
  smtp_secure ENUM('ssl','tls','none') NOT NULL DEFAULT 'ssl',
  username VARCHAR(190) NOT NULL,
  password_enc TEXT NULL,
  from_name VARCHAR(120) NULL,
  reply_to_mode ENUM('same_as_from','user_personal','custom') NOT NULL DEFAULT 'same_as_from',
  reply_to_address VARCHAR(190) NULL,
  append_sent TINYINT(1) NOT NULL DEFAULT 1,
  last_uid INT UNSIGNED NOT NULL DEFAULT 0,
  last_scan_at DATETIME NULL,
  last_scan_status ENUM('ok','error','disabled') NULL,
  last_scan_error TEXT NULL,
  consecutive_failures SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  next_scan_at DATETIME NULL,
  scan_interval_sec SMALLINT UNSIGNED NOT NULL DEFAULT 120,
  active TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_active_scan (active, next_scan_at),
  KEY idx_owner (owner_user_id),
  KEY idx_kind (kind),
  UNIQUE KEY uk_owner_user_id (owner_user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
SQL);
		}

		if ($this->db->getSchema()->getTableSchema('u_yf_mail_account_users', true) === null) {
			$this->execute(<<<'SQL'
CREATE TABLE u_yf_mail_account_users (
  account_id INT UNSIGNED NOT NULL,
  user_id INT NOT NULL,
  can_send TINYINT(1) NOT NULL DEFAULT 1,
  is_default TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (account_id, user_id),
  CONSTRAINT fk_mail_acct_users_account FOREIGN KEY (account_id) REFERENCES u_yf_mail_accounts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
SQL);
		}

		if ($this->db->getSchema()->getTableSchema('u_yf_mail_messages', true) === null) {
			$this->execute(<<<'SQL'
CREATE TABLE u_yf_mail_messages (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  account_id INT UNSIGNED NULL,
  smtp_id INT UNSIGNED NULL,
  sender_user_id INT NULL,
  direction ENUM('in','out') NOT NULL,
  imap_uid INT UNSIGNED NULL,
  message_id VARCHAR(255) NULL,
  in_reply_to VARCHAR(255) NULL,
  references_hdr TEXT NULL,
  date_sent DATETIME NOT NULL,
  from_email VARCHAR(190) NOT NULL,
  from_name VARCHAR(255) NULL,
  to_json JSON NOT NULL,
  cc_json JSON NULL,
  bcc_json JSON NULL,
  subject VARCHAR(998) NOT NULL DEFAULT '',
  body_html MEDIUMTEXT NULL,
  body_text MEDIUMTEXT NULL,
  has_attachments TINYINT(1) NOT NULL DEFAULT 0,
  size_bytes INT UNSIGNED NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_account_imap_uid (account_id, imap_uid),
  KEY idx_message_id (message_id),
  KEY idx_date_sent (date_sent),
  KEY idx_from_email (from_email),
  KEY idx_direction_date (direction, date_sent),
  KEY idx_sender (sender_user_id, direction)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
SQL);
		}

		if ($this->db->getSchema()->getTableSchema('u_yf_mail_attachments', true) === null) {
			$this->execute(<<<'SQL'
CREATE TABLE u_yf_mail_attachments (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  message_id INT UNSIGNED NOT NULL,
  filename VARCHAR(255) NOT NULL,
  original_name VARCHAR(255) NOT NULL,
  mime_type VARCHAR(127) NOT NULL DEFAULT 'application/octet-stream',
  size_bytes INT UNSIGNED NOT NULL DEFAULT 0,
  content_id VARCHAR(255) NULL,
  storage_path VARCHAR(500) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_message (message_id),
  CONSTRAINT fk_mail_attach_message FOREIGN KEY (message_id) REFERENCES u_yf_mail_messages(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
SQL);
		}

		if ($this->db->getSchema()->getTableSchema('u_yf_mail_record_links', true) === null) {
			$this->execute(<<<'SQL'
CREATE TABLE u_yf_mail_record_links (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  message_id INT UNSIGNED NOT NULL,
  crm_module VARCHAR(50) NOT NULL,
  crm_record_id INT UNSIGNED NOT NULL,
  link_type ENUM('auto','manual') NOT NULL DEFAULT 'auto',
  match_field VARCHAR(60) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_message_record (message_id, crm_module, crm_record_id),
  KEY idx_crm_record (crm_module, crm_record_id, message_id),
  CONSTRAINT fk_mail_link_message FOREIGN KEY (message_id) REFERENCES u_yf_mail_messages(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
SQL);
		}

		if ($this->db->getSchema()->getTableSchema('u_yf_mail_log', true) === null) {
			$this->execute(<<<'SQL'
CREATE TABLE u_yf_mail_log (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  account_id INT UNSIGNED NULL,
  user_id INT NULL,
  level ENUM('info','warn','error') NOT NULL DEFAULT 'info',
  action VARCHAR(30) NOT NULL,
  message VARCHAR(500) NOT NULL,
  context_json JSON NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_created (created_at),
  KEY idx_account (account_id),
  KEY idx_level (level)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
SQL);
		}
	}

	private function alterEmailTemplates(): void
	{
		$schema = $this->db->getSchema()->getTableSchema('u_yf_emailtemplates', true);
		if ($schema === null) {
			return;
		}
		if (!isset($schema->columns['sender_type'])) {
			$this->addColumn('u_yf_emailtemplates', 'sender_type', "ENUM('user_account','system_smtp','any') NOT NULL DEFAULT 'system_smtp'");
		}
	}

	private function registerMailTab(): void
	{
		if ((new Query())->from('vtiger_tab')->where(['name' => self::MAIL_TAB_NAME])->exists()) {
			return;
		}
		$this->insert('vtiger_tab', [
			'tabid' => self::MAIL_TABID,
			'name' => self::MAIL_TAB_NAME,
			'presence' => 0,
			'tabsequence' => 0,
			'tablabel' => self::MAIL_TAB_NAME,
			'modifiedby' => 1,
			'modifiedtime' => date('Y-m-d H:i:s'),
			'customized' => 0,
			'ownedby' => 0,
			'isentitytype' => 0,
			'version' => '1.0',
			'parent' => 'Tools',
			'type' => 0,
		]);
	}

	private function registerCronTasks(): void
	{
		$tasks = [
			[
				'name' => 'LBL_MAIL_SCAN',
				'frequency' => 60,
				'laststart' => null,
				'lastend' => null,
				'status' => 1,
				'module' => self::MAIL_TAB_NAME,
				'sequence' => 20,
				'description' => 'Mail inbox scanner',
				'handler_class' => 'App\\Modules\\Mail\\Cron\\Scanner',
				'handler_params' => null,
			],
			[
				'name' => 'LBL_MAIL_LOG_PRUNE',
				'frequency' => 86400,
				'laststart' => null,
				'lastend' => null,
				'status' => 1,
				'module' => self::MAIL_TAB_NAME,
				'sequence' => 21,
				'description' => 'Mail log retention',
				'handler_class' => 'App\\Modules\\Mail\\Cron\\LogPrune',
				'handler_params' => null,
			],
		];
		foreach ($tasks as $task) {
			if ((new Query())->from('vtiger_cron_task')->where(['name' => $task['name']])->exists()) {
				continue;
			}
			$this->insert('vtiger_cron_task', $task);
		}
	}

	private function registerSettingsField(): void
	{
		if ((new Query())->from('vtiger_settings_field')->where(['name' => 'LBL_MAIL_ACCOUNTS'])->exists()) {
			return;
		}
		\App\Modules\Settings\Base\Models\Module::addSettingsField('LBL_MAIL_TOOLS', [
			'name' => 'LBL_MAIL_ACCOUNTS',
			'iconpath' => 'adminIcon-mail-scanner',
			'description' => 'LBL_MAIL_ACCOUNTS_DESCRIPTION',
			'linkto' => 'index.php?module=MailAccount&parent=Settings&view=List',
		]);
	}

	private function registerRelatedLists(): void
	{
		$sequence = 15;
		$nextId = (int) (new Query())->from('vtiger_relatedlists')->max('relation_id') + 1;
		foreach (self::RELATED_MODULES as $tabid => $moduleName) {
			$exists = (new Query())->from('vtiger_relatedlists')
				->where(['tabid' => $tabid, 'related_tabid' => self::MAIL_TABID])
				->exists();
			if ($exists) {
				continue;
			}
			$this->insert('vtiger_relatedlists', [
				'relation_id' => $nextId++,
				'tabid' => $tabid,
				'related_tabid' => self::MAIL_TABID,
				'name' => 'getMails',
				'sequence' => $sequence++,
				'label' => 'LBL_MAILS',
				'presence' => 0,
				'actions' => 'SEND',
				'favorites' => 0,
				'creator_detail' => 0,
				'relation_comment' => 0,
			]);
		}
	}

	private function removeRelatedLists(): void
	{
		$this->db->createCommand()->delete('vtiger_relatedlists', ['related_tabid' => self::MAIL_TABID])->execute();
	}
}
