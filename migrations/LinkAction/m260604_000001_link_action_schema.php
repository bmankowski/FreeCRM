<?php
/**
 * FreeCRM - LinkAction schema, cron, template elements, email template update.
 *
 * Run via: docker compose exec -T app php yii migrate --migrationPath=migrations/LinkAction/ --interactive=0
 */

declare(strict_types=1);

use yii\db\Migration;
use yii\db\Query;

class m260604_000001_link_action_schema extends Migration
{
	public function safeUp(): void
	{
		$this->createLinkActionLogTable();
		$this->registerCronTask();
		$this->seedTemplateElements();
		$this->updateKandydaciEmailTemplate();
	}

	public function safeDown(): void
	{
		$this->db->createCommand(
			"UPDATE u_yf_emailtemplates SET content = REPLACE(content, '\n\n$(dynamic : kandydaci_unsubscribe_footer)$', '') WHERE emailtemplatesid = 1444661"
		)->execute();
		$this->delete('u_yf_templateelements', ['code' => 'kandydaci_unsubscribe_footer']);
		$this->delete('vtiger_crmentity', ['setype' => 'TemplateElements', 'description' => 'kandydaci_unsubscribe_footer']);
		$this->delete('vtiger_cron_task', ['handler_class' => 'App\\Modules\\LinkAction\\Cron\\ImportTask']);
		$this->dropTable('u_yf_link_action_log');
	}

	private function createLinkActionLogTable(): void
	{
		if ($this->db->getSchema()->getTableSchema('u_yf_link_action_log', true) !== null) {
			return;
		}
		$this->execute(<<<'SQL'
CREATE TABLE u_yf_link_action_log (
  id int unsigned NOT NULL AUTO_INCREMENT,
  jti varchar(64) NOT NULL,
  kid varchar(16) NOT NULL,
  module varchar(64) NOT NULL,
  record_id int unsigned NOT NULL,
  action varchar(32) NOT NULL,
  scope varchar(32) NOT NULL,
  email_field varchar(64) NOT NULL,
  eh char(64) NOT NULL,
  token_fp char(64) NOT NULL,
  processed_at datetime NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_link_action_jti (jti),
  KEY idx_link_action_target (module, record_id),
  KEY idx_link_action_processed_at (processed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
SQL);
	}

	private function registerCronTask(): void
	{
		if ((new Query())->from('vtiger_cron_task')->where(['handler_class' => 'App\\Modules\\LinkAction\\Cron\\ImportTask'])->exists()) {
			return;
		}
		$nextId = (int) (new Query())->from('vtiger_cron_task')->max('id', $this->db) + 1;
		$this->insert('vtiger_cron_task', [
			'id' => $nextId,
			'name' => 'LBL_LINK_ACTION_IMPORT',
			'handler_class' => 'App\\Modules\\LinkAction\\Cron\\ImportTask',
			'handler_params' => null,
			'frequency' => 300,
			'laststart' => null,
			'lastend' => null,
			'status' => 1,
			'module' => 'LinkAction',
			'sequence' => 28,
			'description' => 'Pull and import signed link actions from www queue',
		]);
	}

	private function seedTemplateElements(): void
	{
		if ($this->db->getSchema()->getTableSchema('u_yf_templateelements', true) === null) {
			return;
		}

		$elements = [
			[
				'code' => 'kandydaci_unsubscribe_footer',
				'label' => 'LBL_KANDYDACI_UNSUBSCRIBE_FOOTER',
				'language' => 'pl_pl',
				'content' => '<p style="font-size:12px;color:#666;margin-top:24px;">Jeśli nie chcesz otrzymywać od nas więcej wiadomości i chciałbyś, abyśmy usunęli ten email z naszej bazy, możesz się wypisać klikając tutaj: <a href="$(custom : LinkActionUrl|unsubscribe|future_contact|email_prywatny)$">Wypisuje się</a></p>',
				'description' => 'Stopka z linkiem wypisania dla modułu Kandydaci (PL).',
			],
			[
				'code' => 'kandydaci_unsubscribe_footer',
				'label' => 'LBL_KANDYDACI_UNSUBSCRIBE_FOOTER',
				'language' => 'en_us',
				'content' => '<p style="font-size:12px;color:#666;margin-top:24px;">If you no longer wish to receive messages from us and would like us to remove your email from our database, you can unsubscribe here: <a href="$(custom : LinkActionUrl|unsubscribe|future_contact|email_prywatny)$">Unsubscribe</a></p>',
				'description' => 'Unsubscribe footer for Kandydaci module (EN).',
			],
		];

		foreach ($elements as $element) {
			if ((new Query())->from('u_yf_templateelements')->where([
				'code' => $element['code'],
				'module_name' => 'Kandydaci',
				'language' => $element['language'],
			])->exists()) {
				continue;
			}

			$id = $this->nextEntityId();
			$now = date('Y-m-d H:i:s');
			$this->insert('u_yf_templateelements', [
				'templateelementsid' => $id,
				'code' => $element['code'],
				'label' => $element['label'],
				'type' => 'PLL_VARIABLE_ALIAS',
				'module_name' => 'Kandydaci',
				'language' => $element['language'],
				'status' => 1,
				'sequence' => 10,
				'content' => $element['content'],
				'layout_header' => '',
				'layout_body' => '',
				'layout_footer' => '',
				'description' => $element['description'],
			]);
			$this->insert('vtiger_crmentity', [
				'crmid' => $id,
				'smcreatorid' => 1,
				'smownerid' => 1,
				'shownerid' => 0,
				'modifiedby' => 1,
				'setype' => 'TemplateElements',
				'description' => $element['code'],
				'createdtime' => $now,
				'modifiedtime' => $now,
				'presence' => 1,
				'deleted' => 0,
				'was_read' => 0,
				'private' => 0,
			]);
		}
	}

	private function updateKandydaciEmailTemplate(): void
	{
		if ($this->db->getSchema()->getTableSchema('u_yf_emailtemplates', true) === null) {
			return;
		}
		$row = (new Query())->from('u_yf_emailtemplates')->where(['emailtemplatesid' => 1444661])->one();
		if (!$row) {
			return;
		}
		$content = (string) ($row['content'] ?? '');
		if (str_contains($content, 'kandydaci_unsubscribe_footer')) {
			return;
		}
		$this->update('u_yf_emailtemplates', [
			'content' => rtrim($content) . "\n\n$(dynamic : kandydaci_unsubscribe_footer)$",
		], ['emailtemplatesid' => 1444661]);
	}

	private function nextEntityId(): int
	{
		$maxElement = (int) (new Query())->from('u_yf_templateelements')->max('templateelementsid', $this->db);
		$maxCrm = (int) (new Query())->from('vtiger_crmentity')->max('crmid', $this->db);
		return max($maxElement, $maxCrm) + 1;
	}
}
