<?php
/**
 * FreeCRM - AI OpenAI provider config + Settings menu.
 *
 * Run via: docker compose exec -T app php yii migrate --migrationPath=migrations/Users/ --interactive=0
 */

declare(strict_types=1);

use yii\db\Migration;
use yii\db\Query;

class m260722_000002_ai_provider extends Migration
{
	private const SETTINGS_BLOCK = 'LBL_INTEGRATION';
	private const SETTINGS_FIELD = 'LBL_AI_PROVIDER';
	private const TABLE = 's_yf_ai_provider';

	public function safeUp(): void
	{
		$this->createProviderTable();
		$this->seedOpenAi();
		$this->registerSettingsField();
	}

	public function safeDown(): void
	{
		\App\Modules\Settings\Base\Models\Module::deleteSettingsField(self::SETTINGS_BLOCK, self::SETTINGS_FIELD);
		if ($this->db->getSchema()->getTableSchema(self::TABLE, true) !== null) {
			$this->dropTable(self::TABLE);
		}
	}

	private function createProviderTable(): void
	{
		if ($this->db->getSchema()->getTableSchema(self::TABLE, true) !== null) {
			return;
		}
		$this->db->createCommand(<<<'SQL'
CREATE TABLE `s_yf_ai_provider` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `provider` VARCHAR(32) NOT NULL DEFAULT 'openai',
  `api_key` VARCHAR(512) NULL DEFAULT NULL,
  `model` VARCHAR(64) NOT NULL DEFAULT 'gpt-5-nano',
  `modifiedtime` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_ai_provider` (`provider`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
SQL)->execute();
	}

	private function seedOpenAi(): void
	{
		$exists = (new Query())
			->from(self::TABLE)
			->where(['provider' => 'openai'])
			->exists($this->db);
		if ($exists) {
			return;
		}
		$this->insert(self::TABLE, [
			'provider' => 'openai',
			'api_key' => null,
			'model' => 'gpt-5-nano',
			'modifiedtime' => date('Y-m-d H:i:s'),
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
			'description' => 'LBL_AI_PROVIDER_DESCRIPTION',
			'linkto' => 'index.php?module=AiPrompts&parent=Settings&view=Provider',
		]);
	}
}
