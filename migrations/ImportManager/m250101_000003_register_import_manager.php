<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * Registers the ImportManager module in legacy vtiger tables so that
 * permissions and routing work through the standard module loader.
 */

declare(strict_types=1);

use yii\db\Migration;
use yii\db\Query;

class m250101_000003_register_import_manager extends Migration
{
	private const MODULE_NAME = 'ImportManager';
	private const MODULE_LABEL = 'Import Manager';

	public function safeUp(): void
	{
		$db = $this->db;
		$existing = (new Query())
			->from('vtiger_tab')
			->where(['name' => self::MODULE_NAME])
			->exists($db);

		if ($existing) {
			return;
		}

		$nextTabId = ((int) (new Query())->from('vtiger_tab')->max('tabid', $db)) + 1;
		$nextSequence = ((int) (new Query())->from('vtiger_tab')->max('tabsequence', $db)) + 1;

		$db->createCommand()->insert('vtiger_tab', [
			'tabid' => $nextTabId,
			'name' => self::MODULE_NAME,
			'presence' => 0,
			'tabsequence' => max($nextSequence, 200),
			'tablabel' => self::MODULE_LABEL,
			'ownedby' => 0,
			'isentitytype' => 0,
			'parent' => 'Tools',
			'type' => 0,
		])->execute();

		$db->createCommand()->insert('vtiger_tab_info', [
			'tabid' => $nextTabId,
			'prefname' => 'isentitytype',
			'prefvalue' => 0,
		])->execute();

		$profileIds = (new Query())->select('profileid')->from('vtiger_profile')->column($db);
		foreach ($profileIds as $profileId) {
			$this->upsert('vtiger_profile2tab', [
				'profileid' => $profileId,
				'tabid' => $nextTabId,
			], [
				'permissions' => 0,
			]);
		}
	}

	public function safeDown(): void
	{
		$db = $this->db;
		$query = new Query();
		$tabId = $query->select('tabid')
			->from('vtiger_tab')
			->where(['name' => self::MODULE_NAME])
			->scalar($db);

		if (!$tabId) {
			return;
		}

		$db->createCommand()->delete('vtiger_profile2tab', ['tabid' => $tabId])->execute();
		$db->createCommand()->delete('vtiger_tab_info', ['tabid' => $tabId])->execute();
		$db->createCommand()->delete('vtiger_tab', ['tabid' => $tabId])->execute();
	}
}

