<?php
/**
 * FreeCRM - Settings AiPrompts: system default prompts table + menu + seed.
 *
 * Run via: docker compose exec -T app php yii migrate --migrationPath=migrations/Users/ --interactive=0
 */

declare(strict_types=1);

use yii\db\Migration;
use yii\db\Query;

class m260722_000001_ai_prompts extends Migration
{
	private const SETTINGS_BLOCK = 'LBL_INTEGRATION';
	private const SETTINGS_FIELD = 'LBL_AI_PROMPTS';
	private const TABLE = 's_yf_ai_prompts';

	private const SEED_PROMPT = <<<'PROMPT'
You are an assistant that improves business emails.
Rewrite the email to be clearer, more professional, and concise.
Keep the original language of the message.
Do not invent facts. Preserve meaning and any requested call to action.

Subject: {{subject}}

Email body:
{{body}}
PROMPT;

	public function safeUp(): void
	{
		$this->createPromptsTable();
		$this->seedMailImprove();
		$this->registerSettingsField();
	}

	public function safeDown(): void
	{
		\App\Modules\Settings\Base\Models\Module::deleteSettingsField(self::SETTINGS_BLOCK, self::SETTINGS_FIELD);
		if ($this->db->getSchema()->getTableSchema(self::TABLE, true) !== null) {
			$this->dropTable(self::TABLE);
		}
	}

	private function createPromptsTable(): void
	{
		if ($this->db->getSchema()->getTableSchema(self::TABLE, true) !== null) {
			return;
		}
		$this->db->createCommand(<<<'SQL'
CREATE TABLE `s_yf_ai_prompts` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `action_key` VARCHAR(64) NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `prompt_body` MEDIUMTEXT NOT NULL,
  `userid` INT(11) NULL DEFAULT NULL COMMENT 'NULL = system default',
  `owner_scope` INT(11) AS (IFNULL(userid, 0)) STORED,
  `active` TINYINT(1) NOT NULL DEFAULT 1,
  `createdtime` DATETIME NOT NULL,
  `modifiedtime` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_ai_prompts_action_owner` (`action_key`, `owner_scope`),
  KEY `idx_ai_prompts_userid` (`userid`),
  KEY `idx_ai_prompts_active` (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
SQL)->execute();
	}

	private function seedMailImprove(): void
	{
		$exists = (new Query())
			->from(self::TABLE)
			->where(['action_key' => 'mail.improve'])
			->andWhere(['userid' => null])
			->exists($this->db);
		if ($exists) {
			return;
		}
		$now = date('Y-m-d H:i:s');
		$this->insert(self::TABLE, [
			'action_key' => 'mail.improve',
			'name' => 'Improve email',
			'prompt_body' => self::SEED_PROMPT,
			'userid' => null,
			'active' => 1,
			'createdtime' => $now,
			'modifiedtime' => $now,
		]);
	}

	private function registerSettingsField(): void
	{
		$blockId = (int) \vtlib\Deprecated::getSettingsBlockId(self::SETTINGS_BLOCK);
		if ($blockId <= 0) {
			return;
		}
		$exists = (new Query())
			->from('vtiger_settings_field')
			->where(['blockid' => $blockId, 'name' => self::SETTINGS_FIELD])
			->exists($this->db);
		if ($exists) {
			return;
		}
		\App\Modules\Settings\Base\Models\Module::addSettingsField(self::SETTINGS_BLOCK, [
			'name' => self::SETTINGS_FIELD,
			'iconpath' => 'adminIcon-integration',
			'description' => 'LBL_AI_PROMPTS_DESCRIPTION',
			'linkto' => 'index.php?module=AiPrompts&parent=Settings&view=ListView',
		]);
	}
}
