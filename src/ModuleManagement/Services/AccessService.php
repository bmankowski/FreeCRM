<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * @project FreeCRM
 * @author bmankowski@gmail.com
 * @copyright (c) FreeCRM
 * @license FreeCRM Public License 1.0
 */

declare(strict_types=1);

namespace App\ModuleManagement\Services;

/**
 * AccessService class.
 * 
 * Service for access/sharing operations.
 */
class AccessService
{
	/** @var \App\Db\Db Database instance */
	private $db;

	/** @var ProfileService Profile service */
	private $profileService;

	/**
	 * Constructor.
	 * 	 * @param \App\Db\Db $db
	 * @param ProfileService $profileService
	 */
	public function __construct(\App\Db\Db $db, ProfileService $profileService)
	{
		$this->db = $db;
		$this->profileService = $profileService;
	}

	/**
	 * Set default sharing for a module.
	 * 	 * @param int $moduleId Module ID
	 * @param string $permissionText Permission text: 'public_readonly', 'public_readwrite', 'public_readwritedelete', 'private'
	 * @return void
	 */
	public function setDefaultSharing(int $moduleId, string $permissionText = 'public_readwritedelete'): void
	{
		$permissionText = strtolower($permissionText);

		if ($permissionText === 'public_readonly') {
			$permission = 0;
		} elseif ($permissionText === 'public_readwrite') {
			$permission = 1;
		} elseif ($permissionText === 'public_readwritedelete') {
			$permission = 2;
		} elseif ($permissionText === 'private') {
			$permission = 3;
		} else {
			$permission = 2; // public_readwritedelete is default
		}

		$this->db->createCommand()->upsert(
			\App\Security\ModuleSharingDefault::TABLE,
			['tabid' => $moduleId, 'permission' => $permission],
			true
		)->execute();

		$this->syncSharingAccess();
	}

	/**
	 * Initialize sharing access for a module.
	 * 	 * @param int $moduleId Module ID
	 * @return void
	 */
	public function initSharing(int $moduleId): void
	{
		$query = (new \App\Db\Query())
			->select(['share_action_id'])
			->from('vtiger_org_share_action_mapping')
			->where(['share_action_name' => ['Public: Read Only', 'Public: Read, Create/Edit', 'Public: Read, Create/Edit, Delete', 'Private']]);

		$actionIds = $query->column();
		$existingActionIds = (new \App\Db\Query())
			->select(['share_action_id'])
			->from('vtiger_org_share_action2tab')
			->where(['tabid' => $moduleId])
			->column();
		$insertedData = [];

		foreach ($actionIds as $id) {
			if (in_array($id, $existingActionIds, true)) {
				continue;
			}
			$insertedData[] = [$id, $moduleId];
		}

		if (!empty($insertedData)) {
			$this->db->createCommand()
				->batchInsert('vtiger_org_share_action2tab', ['share_action_id', 'tabid'], $insertedData)
				->execute();
		}
	}

	/**
	 * Delete sharing access setup for module.
	 * 	 * @param int $moduleId Module ID
	 * @return void
	 */
	public function deleteSharing(int $moduleId): void
	{
		$this->db->createCommand()
			->delete('vtiger_org_share_action2tab', ['tabid' => $moduleId])
			->execute();
	}

	/**
	 * Delete tools (actions) of the module.
	 * 	 * @param int $moduleId Module ID
	 * @return void
	 */
	public function deleteTools(int $moduleId): void
	{
		$this->db->createCommand()
			->delete('vtiger_profile2utility', ['tabid' => $moduleId])
			->execute();
	}

	/**
	 * Enable or disable tool for module.
	 * 	 * @param int $moduleId Module ID
	 * @param string $toolAction Tool action name (e.g., 'Import', 'Export')
	 * @param bool $enabled True to enable, false to disable
	 * @param int|false $profileId Profile ID to apply to, false for all profiles
	 * @return void
	 */
	public function updateTool(int $moduleId, string $toolAction, bool $enabled, $profileId = false): void
	{
		$actionId = \App\Utils\Utils::getActionid($toolAction);
		if (!$actionId) {
			return;
		}

		$permission = $enabled ? '0' : '1';

		$profileids = [];
		if ($profileId !== false) {
			$profileids[] = $profileId;
		} else {
			$profileids = $this->profileService->getAllIds();
		}

		foreach ($profileids as $useProfileId) {
			$isExists = (new \App\Db\Query())
				->from('vtiger_profile2utility')
				->where([
					'profileid' => $useProfileId,
					'tabid' => $moduleId,
					'activityid' => $actionId
				])
				->exists();

			if ($isExists) {
				$this->db->createCommand()
					->update('vtiger_profile2utility', ['permission' => $permission], [
						'profileid' => $useProfileId,
						'tabid' => $moduleId,
						'activityid' => $actionId
					])
					->execute();
			} else {
				$this->db->createCommand()
					->insert('vtiger_profile2utility', [
						'profileid' => $useProfileId,
						'tabid' => $moduleId,
						'activityid' => $actionId,
						'permission' => $permission
					])
					->execute();
			}
		}
	}

	/**
	 * Recalculate sharing access rules.
	 * 	 * @return void
	 */
	private function syncSharingAccess(): void
	{
		\App\Modules\Users\Services\PrivilegeFileManager::RecalculateSharingRules();
	}
}

