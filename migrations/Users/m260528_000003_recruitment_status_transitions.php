<?php
/**
 * FreeCRM - Recruitment status transition rules tables and Settings menu entry.
 *
 * Run via: yii migrate --migrationPath=migrations/Users/
 */

declare(strict_types=1);

use yii\db\Migration;
use yii\db\Query;

class m260528_000003_recruitment_status_transitions extends Migration
{
	private const SETTINGS_FIELD_NAME = 'LBL_RECRUITMENT';
	private const SETTINGS_BLOCK = 'LBL_PROCESSES';

	public function safeUp(): void
	{
		$schema = $this->db->schema;

		if ($schema->getTableSchema('u_yf_recruitment_status_transitions', true) === null) {
			$this->createTable('u_yf_recruitment_status_transitions', [
				'id' => $this->primaryKey()->unsigned(),
				'from_status' => $this->string(64)->notNull(),
				'to_status' => $this->string(64)->notNull(),
			], 'ENGINE=InnoDB DEFAULT CHARSET=utf8');
			$this->createIndex(
				'u_yf_recruitment_status_transitions_from_to',
				'u_yf_recruitment_status_transitions',
				['from_status', 'to_status'],
				true
			);
		}

		if ($schema->getTableSchema('u_yf_recruitment_settings', true) === null) {
			$this->createTable('u_yf_recruitment_settings', [
				'id' => $this->tinyInteger()->unsigned()->notNull()->defaultValue(1),
				'configured' => $this->tinyInteger(1)->notNull()->defaultValue(0),
			], 'ENGINE=InnoDB DEFAULT CHARSET=utf8');
			$this->addPrimaryKey('u_yf_recruitment_settings_pk', 'u_yf_recruitment_settings', 'id');
		}

		if (!(new Query())->from('u_yf_recruitment_settings')->where(['id' => 1])->exists()) {
			$this->db->createCommand()->insert('u_yf_recruitment_settings', [
				'id' => 1,
				'configured' => 0,
			])->execute();
		}

		$this->registerSettingsMenuEntry();
	}

	public function safeDown(): void
	{
		$this->deleteSettingsMenuEntry();
		$this->dropTable('u_yf_recruitment_status_transitions');
		$this->dropTable('u_yf_recruitment_settings');
	}

	private function registerSettingsMenuEntry(): void
	{
		$blockId = \vtlib\Deprecated::getSettingsBlockId(self::SETTINGS_BLOCK);
		if (!$blockId) {
			return;
		}

		$exists = (new Query())
			->from('vtiger_settings_field')
			->where(['blockid' => $blockId, 'name' => self::SETTINGS_FIELD_NAME])
			->exists();

		if ($exists) {
			return;
		}

		\App\Modules\Settings\Base\Models\Module::addSettingsField(self::SETTINGS_BLOCK, [
			'name' => self::SETTINGS_FIELD_NAME,
			'iconpath' => 'adminIcon-recruitment',
			'description' => 'LBL_RECRUITMENT_DESCRIPTION',
			'linkto' => 'index.php?module=Recruitment&view=Index&parent=Settings',
		]);
	}

	private function deleteSettingsMenuEntry(): void
	{
		\App\Modules\Settings\Base\Models\Module::deleteSettingsField(
			self::SETTINGS_BLOCK,
			self::SETTINGS_FIELD_NAME
		);
	}
}
