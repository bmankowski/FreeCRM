<?php
/**
 * FreeCRM - Recruitment status transition mail prompts table and Settings submenu.
 *
 * Run via: yii migrate --migrationPath=migrations/Users/
 */

declare(strict_types=1);

use yii\db\Migration;
use yii\db\Query;

class m260604_000001_recruitment_status_transition_mail extends Migration
{
	private const SETTINGS_BLOCK = 'LBL_RECRUITMENT';

	public function safeUp(): void
	{
		$schema = $this->db->schema;

		if ($schema->getTableSchema('u_yf_recruitment_status_transition_mail', true) === null) {
			$this->createTable('u_yf_recruitment_status_transition_mail', [
				'id' => $this->primaryKey()->unsigned(),
				'from_status' => $this->string(64)->notNull(),
				'to_status' => $this->string(64)->notNull(),
				'email_template_id' => $this->integer()->unsigned()->notNull(),
			], 'ENGINE=InnoDB DEFAULT CHARSET=utf8');
			$this->createIndex(
				'u_yf_recruitment_status_transition_mail_from_to_tpl',
				'u_yf_recruitment_status_transition_mail',
				['from_status', 'to_status', 'email_template_id'],
				true
			);
		}

		$this->registerSettingsMenuEntry();
	}

	public function safeDown(): void
	{
		$this->deleteSettingsMenuEntry();
		$this->dropTable('u_yf_recruitment_status_transition_mail');
	}

	private function registerSettingsMenuEntry(): void
	{
		$blockId = \vtlib\Deprecated::getSettingsBlockId(self::SETTINGS_BLOCK);
		if (!$blockId) {
			return;
		}

		$name = 'LBL_TRANSITION_MAIL';
		$exists = (new Query())->from('vtiger_settings_field')->where(['blockid' => $blockId, 'name' => $name])->exists();
		$linkto = 'index.php?module=Recruitment&parent=Settings&view=TransitionMail';
		$data = [
			'description' => 'LBL_TRANSITION_MAIL_HELP',
			'iconpath' => 'glyphicon-envelope',
			'linkto' => $linkto,
			'sequence' => 3,
		];

		if ($exists) {
			$this->db->createCommand()->update('vtiger_settings_field', $data, ['blockid' => $blockId, 'name' => $name])->execute();

			return;
		}

		$this->db->createCommand()->insert('vtiger_settings_field', array_merge([
			'blockid' => $blockId,
			'name' => $name,
			'active' => 0,
			'pinned' => 0,
			'admin_access' => null,
		], $data))->execute();
	}

	private function deleteSettingsMenuEntry(): void
	{
		\App\Modules\Settings\Base\Models\Module::deleteSettingsField(self::SETTINGS_BLOCK, 'LBL_TRANSITION_MAIL');
	}
}
