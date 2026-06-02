<?php
/**
 * FreeCRM - Fix double glyphicon class on Recruitment transitions menu item.
 */

declare(strict_types=1);

use yii\db\Migration;

class m260603_000001_recruitment_settings_icon_glyphfix extends Migration
{
	public function safeUp(): void
	{
		$blockId = \vtlib\Deprecated::getSettingsBlockId('LBL_RECRUITMENT');
		if (!$blockId) {
			return;
		}

		$this->db->createCommand()->update('vtiger_settings_field', [
			'iconpath' => 'glyphicon-transfer',
		], ['blockid' => $blockId, 'name' => 'LBL_STATUS_TRANSITIONS'])->execute();
	}

	public function safeDown(): void
	{
	}
}
