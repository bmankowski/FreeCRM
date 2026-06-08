<?php
/**
 * FreeCRM - Outbound mail prepare/sent lifecycle; LinkAction mid; drop mail_context.
 *
 * Run via: docker compose exec -T app php yii migrate --migrationPath=migrations/LinkAction/ --interactive=0
 */

declare(strict_types=1);

use yii\db\Migration;

class m260608_000003_mail_message_id_in_tokens extends Migration
{
	public function safeUp(): void
	{
		$table = $this->db->getSchema()->getTableSchema('u_yf_mail_messages', true);
		if ($table !== null && !isset($table->columns['send_status'])) {
			$this->addColumn(
				'u_yf_mail_messages',
				'send_status',
				"ENUM('prepared','sent','failed') NULL DEFAULT NULL AFTER direction"
			);
			$this->update('u_yf_mail_messages', ['send_status' => 'sent'], ['direction' => 'out']);
			$this->createIndex('idx_mail_messages_send_status', 'u_yf_mail_messages', ['direction', 'send_status']);
		}

		$log = $this->db->getSchema()->getTableSchema('u_yf_link_action_log', true);
		if ($log !== null && !isset($log->columns['mail_message_id'])) {
			$this->addColumn(
				'u_yf_link_action_log',
				'mail_message_id',
				'INT UNSIGNED NULL DEFAULT NULL AFTER record_id'
			);
			$this->createIndex('idx_link_action_mail_message', 'u_yf_link_action_log', 'mail_message_id');
		}

		if ($this->db->getSchema()->getTableSchema('u_yf_link_action_mail_context', true) !== null) {
			$this->dropTable('u_yf_link_action_mail_context');
		}
	}

	public function safeDown(): void
	{
		if ($this->db->getSchema()->getTableSchema('u_yf_link_action_mail_context', true) === null) {
			$this->execute(<<<'SQL'
CREATE TABLE u_yf_link_action_mail_context (
  jti varchar(64) NOT NULL,
  mail_queue_id int unsigned DEFAULT NULL,
  email_template_id int unsigned DEFAULT NULL,
  subject varchar(998) NOT NULL DEFAULT '',
  module varchar(64) DEFAULT NULL,
  record_id int unsigned DEFAULT NULL,
  sent_at datetime NOT NULL,
  PRIMARY KEY (jti),
  KEY idx_link_action_mail_context_queue (mail_queue_id),
  KEY idx_link_action_mail_context_target (module, record_id),
  KEY idx_link_action_mail_context_sent_at (sent_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
SQL);
		}

		$log = $this->db->getSchema()->getTableSchema('u_yf_link_action_log', true);
		if ($log !== null && isset($log->columns['mail_message_id'])) {
			$this->dropIndex('idx_link_action_mail_message', 'u_yf_link_action_log');
			$this->dropColumn('u_yf_link_action_log', 'mail_message_id');
		}

		$table = $this->db->getSchema()->getTableSchema('u_yf_mail_messages', true);
		if ($table !== null && isset($table->columns['send_status'])) {
			$this->dropIndex('idx_mail_messages_send_status', 'u_yf_mail_messages');
			$this->dropColumn('u_yf_mail_messages', 'send_status');
		}
	}
}
