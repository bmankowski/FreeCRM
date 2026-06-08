<?php
/**
 * FreeCRM - LinkAction mail send context (jti → subject / queue row).
 *
 * Run via: docker compose exec -T app php yii migrate --migrationPath=migrations/LinkAction/ --interactive=0
 */

declare(strict_types=1);

use yii\db\Migration;

class m260608_000002_link_action_mail_context extends Migration
{
	public function safeUp(): void
	{
		if ($this->db->getSchema()->getTableSchema('u_yf_link_action_mail_context', true) !== null) {
			return;
		}
		$this->execute(<<<'SQL'
CREATE TABLE u_yf_link_action_mail_context (
  jti varchar(64) NOT NULL,
  mail_queue_id int unsigned DEFAULT NULL,
  email_template_id int unsigned DEFAULT NULL,
  subject varchar(512) NOT NULL DEFAULT '',
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

	public function safeDown(): void
	{
		$this->dropTable('u_yf_link_action_mail_context');
	}
}
