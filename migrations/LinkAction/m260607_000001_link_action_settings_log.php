<?php
/**
 * FreeCRM - LinkAction Settings log UI: clicked_at column + Settings menu entry.
 *
 * Run via: docker compose exec -T app php yii migrate --migrationPath=migrations/LinkAction/ --interactive=0
 */

declare(strict_types=1);

use yii\db\Migration;
use yii\db\Query;

class m260607_000001_link_action_settings_log extends Migration
{
	private const SETTINGS_BLOCK = 'LBL_LOGS';
	private const SETTINGS_FIELD = 'LBL_LINK_ACTION_LOG';

	public function safeUp(): void
	{
		$this->addClickedAtColumn();
		$this->registerSettingsMenu();
	}

	public function safeDown(): void
	{
		\App\Modules\Settings\Base\Models\Module::deleteSettingsField(self::SETTINGS_BLOCK, self::SETTINGS_FIELD);

		$table = $this->db->getSchema()->getTableSchema('u_yf_link_action_log', true);
		if ($table !== null && isset($table->columns['clicked_at'])) {
			$this->dropIndex('idx_link_action_clicked_at', 'u_yf_link_action_log');
			$this->dropColumn('u_yf_link_action_log', 'clicked_at');
		}
	}

	private function addClickedAtColumn(): void
	{
		$table = $this->db->getSchema()->getTableSchema('u_yf_link_action_log', true);
		if ($table === null || isset($table->columns['clicked_at'])) {
			return;
		}

		$this->addColumn('u_yf_link_action_log', 'clicked_at', $this->dateTime()->null()->after('processed_at'));
		$this->createIndex('idx_link_action_clicked_at', 'u_yf_link_action_log', 'clicked_at');
		$this->update('u_yf_link_action_log', ['clicked_at' => new \yii\db\Expression('processed_at')], ['clicked_at' => null]);
	}

	private function registerSettingsMenu(): void
	{
		$blockId = \vtlib\Deprecated::getSettingsBlockId(self::SETTINGS_BLOCK);
		if (!$blockId) {
			return;
		}

		$exists = (new Query())->from('vtiger_settings_field')->where([
			'blockid' => $blockId,
			'name' => self::SETTINGS_FIELD,
		])->exists();

		if ($exists) {
			return;
		}

		\App\Modules\Settings\Base\Models\Module::addSettingsField(self::SETTINGS_BLOCK, [
			'name' => self::SETTINGS_FIELD,
			'iconpath' => 'glyphicon-link',
			'description' => 'LBL_LINK_ACTION_LOG_DESCRIPTION',
			'linkto' => 'index.php?module=LinkAction&parent=Settings&view=ListView',
			'active' => 0,
			'pinned' => 0,
			'admin_access' => null,
		]);
	}
}
