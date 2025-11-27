<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * Adds the ImportData permission action and assigns it to existing profiles.
 */

declare(strict_types=1);

use yii\db\Migration;
use yii\db\Query;

class m250101_000002_setup_import_permissions extends Migration
{
	private const ACTION_NAME = 'ImportData';

	public function safeUp(): void
	{
		$actionId = $this->ensureActionExists();

		$modules = (new Query())
			->select('tabid')
			->from('vtiger_tab')
			->where(['isentitytype' => 1])
			->andWhere(['<>', 'name', 'Users'])
			->column($this->db);

		$profiles = (new Query())
			->select('profileid')
			->from('vtiger_profile')
			->column($this->db);

		foreach ($modules as $tabId) {
			foreach ($profiles as $profileId) {
				$this->upsert('vtiger_profile2utility', [
					'profileid' => $profileId,
					'tabid' => $tabId,
					'activityid' => $actionId,
				], [
					'permission' => '0',
				]);
			}
		}
	}

	public function safeDown(): void
	{
		$actionId = $this->getActionId();
		if (!$actionId) {
			return;
		}

		$this->delete('vtiger_profile2utility', ['activityid' => $actionId]);
		$this->delete('vtiger_actionmapping', ['actionid' => $actionId]);
	}

	private function ensureActionExists(): int
	{
		$existingId = $this->getActionId();
		if ($existingId) {
			return $existingId;
		}

		$maxActionId = (int)(new Query())
			->from('vtiger_actionmapping')
			->max('actionid', $this->db);
		$nextId = $maxActionId + 1;

		$this->insert('vtiger_actionmapping', [
			'actionid' => $nextId,
			'actionname' => self::ACTION_NAME,
			'securitycheck' => 0,
		]);

		return $nextId;
	}

	private function getActionId(): ?int
	{
		$actionId = (new Query())
			->select('actionid')
			->from('vtiger_actionmapping')
			->where(['actionname' => self::ACTION_NAME])
			->scalar($this->db);

		return $actionId ? (int)$actionId : null;
	}
}

