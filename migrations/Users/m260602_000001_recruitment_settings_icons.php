<?php
/**
 * FreeCRM - Fix Recruitment settings menu icons (use existing icon font classes).
 *
 * Run via: yii migrate --migrationPath=migrations/Users/
 */

declare(strict_types=1);

use yii\db\Migration;
use yii\db\Query;

class m260602_000001_recruitment_settings_icons extends Migration
{
	private const BLOCK = 'LBL_RECRUITMENT';

	public function safeUp(): void
	{
		$blockId = \vtlib\Deprecated::getSettingsBlockId(self::BLOCK);
		if (!$blockId) {
			return;
		}

		$this->db->createCommand()->update('vtiger_settings_blocks', [
			'icon' => 'userIcon-ProjektyRekrutacyjne',
		], ['blockid' => $blockId])->execute();

		$this->db->createCommand()->update('vtiger_settings_field', [
			'iconpath' => 'glyphicon-transfer',
		], ['blockid' => $blockId, 'name' => 'LBL_STATUS_TRANSITIONS'])->execute();

		$this->db->createCommand()->update('vtiger_settings_field', [
			'iconpath' => 'adminIcon-workflow',
		], ['blockid' => $blockId, 'name' => 'LBL_RECRUITMENT_WORKFLOWS'])->execute();
	}

	public function safeDown(): void
	{
		$blockId = \vtlib\Deprecated::getSettingsBlockId(self::BLOCK);
		if (!$blockId) {
			return;
		}

		$this->db->createCommand()->update('vtiger_settings_blocks', [
			'icon' => 'adminIcon-recruitment',
		], ['blockid' => $blockId])->execute();

		$this->db->createCommand()->update('vtiger_settings_field', [
			'iconpath' => 'adminIcon-recruitment',
		], ['blockid' => $blockId, 'name' => 'LBL_STATUS_TRANSITIONS'])->execute();

		$this->db->createCommand()->update('vtiger_settings_field', [
			'iconpath' => 'adminIcon-automation',
		], ['blockid' => $blockId, 'name' => 'LBL_RECRUITMENT_WORKFLOWS'])->execute();
	}
}
