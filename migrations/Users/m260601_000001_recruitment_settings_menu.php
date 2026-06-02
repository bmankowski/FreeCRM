<?php
/**
 * FreeCRM - Dedicated Recruitment settings block with Transitions and Workflows submenus.
 *
 * Run via: yii migrate --migrationPath=migrations/Users/
 */

declare(strict_types=1);

use yii\db\Migration;
use yii\db\Query;

class m260601_000001_recruitment_settings_menu extends Migration
{
	private const OLD_BLOCK = 'LBL_PROCESSES';
	private const NEW_BLOCK = 'LBL_RECRUITMENT';
	private const OLD_FIELD = 'LBL_RECRUITMENT';

	public function safeUp(): void
	{
		$this->deleteSettingsField(self::OLD_BLOCK, self::OLD_FIELD);
		$blockId = $this->ensureRecruitmentBlock();
		$this->addMenuField($blockId, 'LBL_STATUS_TRANSITIONS', 'LBL_STATUS_TRANSITIONS_HELP', 'glyphicon-transfer', 1, 'index.php?module=Recruitment&parent=Settings&view=Transitions');
		$this->addMenuField($blockId, 'LBL_RECRUITMENT_WORKFLOWS', 'LBL_RECRUITMENT_WORKFLOWS_HELP', 'adminIcon-workflow', 2, 'index.php?module=Recruitment&parent=Settings&view=Workflows');
	}

	public function safeDown(): void
	{
		$blockId = \vtlib\Deprecated::getSettingsBlockId(self::NEW_BLOCK);
		if ($blockId) {
			$this->db->createCommand()->delete('vtiger_settings_field', ['blockid' => $blockId])->execute();
			$this->db->createCommand()->delete('vtiger_settings_blocks', ['blockid' => $blockId])->execute();
		}

		$processBlockId = \vtlib\Deprecated::getSettingsBlockId(self::OLD_BLOCK);
		if ($processBlockId && !(new Query())->from('vtiger_settings_field')->where(['blockid' => $processBlockId, 'name' => self::OLD_FIELD])->exists()) {
			\App\Modules\Settings\Base\Models\Module::addSettingsField(self::OLD_BLOCK, [
				'name' => self::OLD_FIELD,
				'iconpath' => 'adminIcon-recruitment',
				'description' => 'LBL_RECRUITMENT_DESCRIPTION',
				'linkto' => 'index.php?module=Recruitment&parent=Settings&view=Index',
			]);
		}
	}

	private function ensureRecruitmentBlock(): int
	{
		$blockId = \vtlib\Deprecated::getSettingsBlockId(self::NEW_BLOCK);
		if ($blockId) {
			return (int) $blockId;
		}

		$adb = \App\Database\PearDatabase::getInstance();
		$blockId = (int) $adb->getUniqueID('vtiger_settings_blocks');
		$sequence = (int) (new Query())->from('vtiger_settings_blocks')->max('sequence');

		$this->db->createCommand()->insert('vtiger_settings_blocks', [
			'blockid' => $blockId,
			'label' => self::NEW_BLOCK,
			'sequence' => $sequence + 1,
			'icon' => 'userIcon-ProjektyRekrutacyjne',
			'type' => 0,
			'linkto' => null,
			'admin_access' => null,
		])->execute();

		$this->db->createCommand()->update('vtiger_settings_blocks_seq', ['id' => $blockId])->execute();

		return $blockId;
	}

	private function addMenuField(int $blockId, string $name, string $description, string $icon, int $sequence, string $linkto): void
	{
		$exists = (new Query())->from('vtiger_settings_field')->where(['blockid' => $blockId, 'name' => $name])->exists();
		if ($exists) {
			$this->db->createCommand()->update('vtiger_settings_field', [
				'description' => $description,
				'iconpath' => $icon,
				'linkto' => $linkto,
				'sequence' => $sequence,
			], ['blockid' => $blockId, 'name' => $name])->execute();

			return;
		}

		$this->db->createCommand()->insert('vtiger_settings_field', [
			'blockid' => $blockId,
			'name' => $name,
			'iconpath' => $icon,
			'description' => $description,
			'linkto' => $linkto,
			'sequence' => $sequence,
			'active' => 0,
			'pinned' => 0,
			'admin_access' => null,
		])->execute();
	}

	private function deleteSettingsField(string $block, string $name): void
	{
		\App\Modules\Settings\Base\Models\Module::deleteSettingsField($block, $name);
	}
}
