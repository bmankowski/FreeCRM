<?php
namespace App\Security;

/**
 * Privilege basic class
 * @package YetiForce.App
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 */
class Privilege
{

	public static $isPermittedLevel;
	protected static $userSharingCache = [];

	/**
	 * Get sharing privileges from file by id
	 * @param int $userId
	 * @return array|null
	 */
	public static function getSharingFile($userId)
	{
		if (isset(self::$userSharingCache[$userId])) {
			return self::$userSharingCache[$userId];
		}
		if (!file_exists("user_privileges/sharing_privileges_{$userId}.php")) {
			return null;
		}
		$sharingPrivileges = require("user_privileges/sharing_privileges_{$userId}.php");
		self::$userSharingCache[$userId] = $sharingPrivileges;
		return $sharingPrivileges;
	}

	/**
	 * Function to check permission for a Module/Action/Record
	 * Refactored for better maintainability and debugging
	 * 
	 * @param string $moduleName Module name to check
	 * @param string|null $actionName Action to perform
	 * @param int|bool $record Record ID (false for module-level checks)
	 * @param int|bool $userId User ID (defaults to current user)
	 * @return bool True if permitted, false otherwise
	 */
	public static function isPermitted($moduleName, $actionName = null, $record = false, $userId = false)
	{
	
	// Step 1: Initialize user ID and load privileges
	if (!$userId) {
		$userId = \App\Modules\Users\Models\Record::getCurrentUserId();
	}
	
	$userPrivileges = \App\Modules\Users\Models\Privileges::getPrivilegesFile($userId);
	if ($userPrivileges === null) {
		return static::returnPermissionResult(false, 'SEC_USER_PRIVILEGES_NOT_FOUND', "User privileges file not found for user: $userId");
	}
		
		// Step 2: Check modules without security restrictions
		if (static::checkModulesWithoutSecurity($moduleName)) {
			return static::returnPermissionResult(true, 'SEC_MODULE_DONT_HAVE_SECURITY_RIGHT');
		}
		
		// Step 3: Check Settings/Administration module access
		$settingsCheck = static::checkSettingsModuleAccess($moduleName, $userPrivileges);
		if ($settingsCheck !== null) {
			return $settingsCheck;
		}
		
		// Step 4: Get module and action IDs
		$checkModule = ($moduleName == 'Events') ? 'Calendar' : $moduleName;
		$tabid = \App\Utils\ModuleUtils::getModuleId($moduleName);
		$actionid = \App\Utils\Utils::getActionid($actionName);
		
		// Step 5: Check if module is active
		if (!\App\Utils\ModuleUtils::isModuleActive($checkModule)) {
			return static::returnPermissionResult(false, 'SEC_MODULE_IS_INACTIVE');
		}
		
		// Step 6: Check admin privileges
		$adminCheck = static::checkAdminPrivileges($userPrivileges, $record);
		if ($adminCheck !== null) {
			return $adminCheck;
		}
		
		// Step 7: Check module and action permissions
		$moduleActionCheck = static::checkModuleAndActionPermissions($userPrivileges, $tabid, $actionid);
		if ($moduleActionCheck !== null) {
			return $moduleActionCheck;
		}
		
		// Step 8: Check global permissions (View All / Edit All)
		$globalCheck = static::checkGlobalPermissions($userPrivileges, $actionid);
		if ($globalCheck !== null) {
			return $globalCheck;
		}
		
		// Step 9: Check record-level permissions
		$recordCheck = static::checkRecordLevelPermissions($moduleName, $tabid, $actionid, $record, $userId, $userPrivileges);
		
		\App\Log\Log::trace('Exiting isPermitted method ... - ' . static::$isPermittedLevel);
		return $recordCheck;
	}
	
	/**
	 * Helper method to return permission result with logging
	 */
	private static function returnPermissionResult($permitted, $level, $errorMsg = null)
	{
		static::$isPermittedLevel = $level;
		if ($errorMsg) {
			\App\Log\Log::error($errorMsg);
		}
		\App\Log\Log::trace("Exiting isPermitted method ... - $level");
		return $permitted;
	}
	
	/**
	 * Check if module has no security restrictions (Users, Home, uploads)
	 * @param string $moduleName
	 * @param \App\Http\Vtiger_Request|null $request
	 */
	private static function checkModulesWithoutSecurity($moduleName, $request = null)
	{
		return in_array($moduleName, ['Users', 'Home', 'uploads']) 
			&& ($request === null || $request->get('parenttab') != 'Settings');
	}
	
	/**
	 * Check access to Settings/Administration modules
	 * @param string $moduleName
	 * @param array $userPrivileges
	 * @param \App\Http\Vtiger_Request|null $request
	 */
	private static function checkSettingsModuleAccess($moduleName, $userPrivileges, $request = null)
	{
		$settingsModules = ['Settings', 'Administration', 'System'];
		if (in_array($moduleName, $settingsModules) || ($request !== null && $request->get('parenttab') == 'Settings')) {
			$permission = $userPrivileges['is_admin'];
			$level = 'SEC_ADMINISTRATION_MODULE_' . ($permission ? 'YES' : 'NO');
			return static::returnPermissionResult($permission, $level);
		}
		return null;
	}
	
	/**
	 * Check if user has admin privileges
	 */
	private static function checkAdminPrivileges($userPrivileges, $record)
	{
		if (!$userPrivileges['is_admin']) {
			return null;
		}
		
		// Admin users have access, but check if record exists and is not deleted
		if ($record) {
			$recordMetaData = \vtlib\Functions:: getCRMRecordMetadata($record);
			if (!isset($recordMetaData) || $recordMetaData['deleted'] === 1) {
				return static::returnPermissionResult(false, 'SEC_RECORD_DOES_NOT_EXIST');
			}
		}
		
		return static::returnPermissionResult(true, 'SEC_USER_IS_ADMIN');
	}
	
	/**
	 * Check module tab and action permissions
	 */
	private static function checkModuleAndActionPermissions($userPrivileges, $tabid, $actionid)
	{
		// If no action specified, check tab permission only
		if ($actionid === '' || $actionid === null) {
			$permission = isset($userPrivileges['profile_tabs_permission'][$tabid]) 
				&& $userPrivileges['profile_tabs_permission'][$tabid] == 0;
			$level = 'SEC_USER_IS_ADMIN' . ($permission ? 'YES' : 'NO');
			return static::returnPermissionResult($permission, $level);
		}
		
		// Check tab permission
		if (!isset($userPrivileges['profile_tabs_permission'][$tabid]) 
			|| $userPrivileges['profile_tabs_permission'][$tabid] != 0) {
			return static::returnPermissionResult(false, 'SEC_MODULE_PERMISSIONS_NO');
		}
		
		// Check if action exists
		if (empty($actionid)) {
			return static::returnPermissionResult(false, 'SEC_ACTION_DOES_NOT_EXIST');
		}
		
		// Check action permission
		if (!isset($userPrivileges['profile_action_permission'][$tabid][$actionid])) {
			return static::returnPermissionResult(false, 'SEC_MODULE_NO_ACTION_TOOL');
		}
		
		$actionPermission = $userPrivileges['profile_action_permission'][$tabid][$actionid];
		
		// Empty string means allowed
		if (strlen($actionPermission) < 1 && $actionPermission == '') {
			return static::returnPermissionResult(true, 'SEC_MODULE_RIGHTS_TO_ACTION');
		}
		
		// Non-zero means denied
		if ($actionPermission != 0 && $actionPermission != '') {
			return static::returnPermissionResult(false, 'SEC_MODULE_NO_RIGHTS_TO_ACTION');
		}
		
		return null;
	}
	
	/**
	 * Check global permissions (View All / Edit All)
	 */
	private static function checkGlobalPermissions($userPrivileges, $actionid)
	{
		// Check View All permission (actions 3, 4 = DetailView, ListView)
		if (($userPrivileges['profile_global_permission'][1] == 0 
			|| $userPrivileges['profile_global_permission'][2] == 0)
			&& ($actionid == 3 || $actionid == 4)) {
			return static::returnPermissionResult(true, 'SEC_MODULE_VIEW_ALL_PERMISSION');
		}
		
		// Check Edit All permission (actions 0, 1, 3, 4 = Save, EditView, DetailView, ListView)
		if ($userPrivileges['profile_global_permission'][2] == 0
			&& in_array($actionid, [0, 1, 3, 4])) {
			return static::returnPermissionResult(true, 'SEC_MODULE_EDIT_ALL_PERMISSION');
		}
		
		return null;
	}
	
	/**
	 * Check all record-level permissions
	 */
	private static function checkRecordLevelPermissions($moduleName, $tabid, $actionid, $record, $userId, $userPrivileges)
	{
		// If no record specified, allow access
		if (empty($record)) {
			return static::returnPermissionResult(true, 'SEC_RECORID_IS_NULL');
		}
		
		// Check if module doesn't use sharing (Products, Vendors, FAQ, PriceBooks)
		if (\vtlib\Functions:: getModuleOwner($moduleName) == 1) {
			return static::returnPermissionResult(true, 'SEC_MODULE_IS_OWNEDBY');
		}
		
		// Get record metadata
		$recordMetaData = \vtlib\Functions:: getCRMRecordMetadata($record);
		if (!isset($recordMetaData) || $recordMetaData['deleted'] === 1) {
			return static::returnPermissionResult(false, 'SEC_RECORD_DOES_NOT_EXIST');
		}
		
		// Check private record permission
		$privateCheck = static::checkPrivateRecordPermission($record, $moduleName, $recordMetaData, $userId, $userPrivileges);
		if ($privateCheck !== null) {
			return $privateCheck;
		}
		
		// Check advanced permissions
		$advancedCheck = static::checkAdvancedPermission($record, $moduleName, $userId);
		if ($advancedCheck !== null) {
			return $advancedCheck;
		}
		
		// Check shared owner permission
		$sharedOwnerCheck = static::checkSharedOwnerPermission($record, $moduleName, $userId, $userPrivileges);
		if ($sharedOwnerCheck !== null) {
			return $sharedOwnerCheck;
		}
		
		// Check record ownership
		$ownershipCheck = static::checkRecordOwnership($recordMetaData, $userId, $userPrivileges);
		if ($ownershipCheck !== null) {
			return $ownershipCheck;
		}
		
		// Check record hierarchy permission
		$hierarchyCheck = static::checkRecordHierarchy($record, $moduleName, $tabid, $actionid, $userId, $userPrivileges, $recordMetaData);
		if ($hierarchyCheck !== null) {
			return $hierarchyCheck;
		}
		
		// Final check: sharing rules
		return static::checkSharingPermission($moduleName, $tabid, $actionid, $record, $userId);
	}
	
	/**
	 * Check private record permission
	 */
	private static function checkPrivateRecordPermission($record, $moduleName, $recordMetaData, $userId, $userPrivileges)
	{
		if (!\App\Core\AppConfig::security('PERMITTED_BY_PRIVATE_FIELD') || !$recordMetaData['private']) {
			return null;
		}
		
		$level = 'SEC_PRIVATE_RECORD_NO';
		$isPermittedPrivateRecord = false;
		$recOwnId = $recordMetaData['smownerid'];
		$recOwnType = \App\Fields\Owner::getType($recOwnId);
		
		// Check if user is the owner
		if ($recOwnType === 'Users' && $userId === $recOwnId) {
			$level = 'SEC_PRIVATE_RECORD_OWNER_CURRENT_USER';
			$isPermittedPrivateRecord = true;
		}
		
		// Check if user's group is the owner
		if ($recOwnType === 'Groups' && in_array($recOwnId, $userPrivileges['groups'])) {
			$level = 'SEC_PRIVATE_RECORD_OWNER_CURRENT_GROUP';
			$isPermittedPrivateRecord = true;
		}
		
		// Check if user is a shared owner
		if (!$isPermittedPrivateRecord) {
			$shownerids = \App\Modules\Base\UiTypes\SharedOwner::getSharedOwners($record, $moduleName);
			if (in_array($userId, $shownerids) 
				|| count(array_intersect($shownerids, $userPrivileges['groups'])) > 0) {
				$level = 'SEC_PRIVATE_RECORD_SHARED_OWNER';
				$isPermittedPrivateRecord = true;
			}
		}
		
		return static::returnPermissionResult($isPermittedPrivateRecord, $level);
	}
	
	/**
	 * Check advanced permissions
	 */
	private static function checkAdvancedPermission($record, $moduleName, $userId)
	{
		if (!\App\Core\AppConfig::security('PERMITTED_BY_ADVANCED_PERMISSION')) {
			return null;
		}
		
		$prvAdv = \App\Security\PrivilegeAdvanced::checkPermissions($record, $moduleName, $userId);
		if ($prvAdv === false) {
			return null;
		}
		
		$permitted = ($prvAdv !== 0);
		$level = $permitted ? 'SEC_ADVANCED_PERMISSION_YES' : 'SEC_ADVANCED_PERMISSION_NO';
		return static::returnPermissionResult($permitted, $level);
	}
	
	/**
	 * Check shared owner permission
	 */
	private static function checkSharedOwnerPermission($record, $moduleName, $userId, $userPrivileges)
	{
		if (!\App\Core\AppConfig::security('PERMITTED_BY_SHARED_OWNERS')) {
			return null;
		}
		
		$shownerids = \App\Modules\Base\UiTypes\SharedOwner::getSharedOwners($record, $moduleName);
		if (in_array($userId, $shownerids) 
			|| count(array_intersect($shownerids, $userPrivileges['groups'])) > 0) {
			return static::returnPermissionResult(true, 'SEC_RECORD_SHARED_OWNER');
		}
		
		return null;
	}
	
	/**
	 * Check record ownership (user or group)
	 */
	private static function checkRecordOwnership($recordMetaData, $userId, $userPrivileges)
	{
		$recOwnId = $recordMetaData['smownerid'];
		$recOwnType = \App\Fields\Owner::getType($recOwnId);
		
		if ($recOwnType == 'Users') {
			// Check if current user is the owner
			if ($userId == $recOwnId) {
				return static::returnPermissionResult(true, 'SEC_RECORD_OWNER_CURRENT_USER');
			}
			
			// Check if owner is a subordinate user
			if (\App\Core\AppConfig::security('PERMITTED_BY_ROLES')) {
				foreach ($userPrivileges['subordinate_roles_users'] as &$userids) {
					if (in_array($recOwnId, $userids)) {
						return static::returnPermissionResult(true, 'SEC_RECORD_OWNER_SUBORDINATE_USER');
					}
				}
			}
		} elseif ($recOwnType == 'Groups') {
			// Check if record is owned by user's group
			if (in_array($recOwnId, $userPrivileges['groups'])) {
				return static::returnPermissionResult(true, 'SEC_RECORD_OWNER_CURRENT_GROUP');
			}
		}
		
		return null;
	}
	
	/**
	 * Check record hierarchy permission (related records)
	 */
	private static function checkRecordHierarchy($record, $moduleName, $tabid, $actionid, $userId, $userPrivileges, $recordMetaData)
	{
		if (!\App\Core\AppConfig::security('PERMITTED_BY_RECORD_HIERARCHY')) {
			return null;
		}
		
		$userPrivilegesModel = \App\Modules\Users\Models\Privileges::getInstanceById($userId);
		$role = $userPrivilegesModel->getRoleDetail();
		
		// Check if role allows preview/edit of related records
		$allowPreview = ($actionid == 3 || $actionid == 4) && $role->get('previewrelatedrecord') != 0;
		$allowEdit = ($actionid == 0 || $actionid == 1) && $role->get('editrelatedrecord') != 0;
		
		if (!$allowPreview && !$allowEdit) {
			return null;
		}
		
		$parentRecord = \App\Modules\Users\Models\Privileges::getParentRecord(
			$record, $moduleName, $role->get('previewrelatedrecord'), $actionid
		);
		
		if (!$parentRecord) {
			return null;
		}
		
		$parentMetaData = \vtlib\Functions:: getCRMRecordMetadata($parentRecord);
		$permissionsRelatedField = $role->get('permissionsrelatedfield');
		$permissionsArray = ($permissionsRelatedField == '') ? [] : explode(',', $permissionsRelatedField);
		
		foreach ($permissionsArray as &$row) {
			$relatedPermission = false;
			
			switch ($row) {
				case 0: // Owner check
					$relatedPermission = $parentMetaData['smownerid'] == $userId 
						|| in_array($parentMetaData['smownerid'], $userPrivileges['groups']);
					break;
				case 1: // Shared owner check
					$relatedPermission = in_array($userId, 
						\App\Modules\Base\UiTypes\SharedOwner::getSharedOwners($parentRecord, $parentMetaData['setype']));
					break;
				case 2: // Sharing rules check
					if (\App\Core\AppConfig::security('PERMITTED_BY_SHARING')) {
						$relatedPermission = static::isPermittedBySharing(
							$parentMetaData['setype'], 
							\App\Utils\ModuleUtils::getModuleId($parentMetaData['setype']), 
							$actionid, 
							$parentRecord, 
							$userId
						);
					}
					break;
			}
			
			if ($relatedPermission) {
				return static::returnPermissionResult(true, 'SEC_RECORD_HIERARCHY_USER');
			}
		}
		
		return null;
	}
	
	/**
	 * Check sharing permission (final fallback)
	 */
	private static function checkSharingPermission($moduleName, $tabid, $actionid, $record, $userId)
	{
		$permission = false;
		
		if (\App\Core\AppConfig::security('PERMITTED_BY_SHARING')) {
			$permission = static::isPermittedBySharing($moduleName, $tabid, $actionid, $record, $userId);
		}
		
		$level = 'SEC_RECORD_BY_SHARING_' . ($permission ? 'YES' : 'NO');
		return static::returnPermissionResult($permission, $level);
	}

public static function isPermittedBySharing($moduleName, $tabId, $actionId, $recordId, $userId)
{
	$sharingPrivileges = self::getSharingFile($userId);
	//Retreiving the default Organisation sharing Access
	$othersPermissionId = $sharingPrivileges['defOrgShare'][$tabId];
		//Checking for Default Org Sharing permission
		if ($othersPermissionId == 0) {
			if ($actionId == 1 || $actionId == 0) {
				return static::isReadWritePermittedBySharing($moduleName, $tabId, $actionId, $recordId, $userId);
			} elseif ($actionId == 2) {
				return false;
			} else {
				return true;
			}
		} elseif ($othersPermissionId == 1) {
			if ($actionId == 2) {
				return false;
			} else {
				return true;
			}
		} elseif ($othersPermissionId == 2) {
			return true;
		} elseif ($othersPermissionId == 3) {
			if ($actionId == 3 || $actionId == 4) {
				return static::isReadPermittedBySharing($moduleName, $tabId, $actionId, $recordId, $userId);
			} elseif ($actionId == 0 || $actionId == 1) {
				return static::isReadWritePermittedBySharing($moduleName, $tabId, $actionId, $recordId, $userId);
			} elseif ($actionId == 2) {
				return false;
			} else {
				return true;
			}
		} else {
			return true;
		}
		return false;
	}

	/** Function to check if the currently logged in user has Read Access due to Sharing for the specified record
	 * @param $moduleName -- Module Name:: Type varchar
	 * @param $actionId -- Action Id:: Type integer
	 * @param $recordId -- Record Id:: Type integer
	 * @param $tabId -- Tab Id:: Type integer
	 * @returns yes or no. If Yes means this action is allowed for the currently logged in user. If no means this action is not allowed for the currently logged in user
	 */
	public static function isReadPermittedBySharing($moduleName, $tabId, $actionId, $recordId, $userId)
	{
		$sharingPrivileges = self::getSharingFile($userId);

		if (!isset($sharingPrivileges['permission'][$moduleName])) {
			return false;
		}
		$sharingPrivilegesModule = $sharingPrivileges['permission'][$moduleName];

		$recordMetaData = \vtlib\Functions:: getCRMRecordMetadata($recordId);
		$ownerId = $recordMetaData['smownerid'];
		$ownerType = \App\Fields\Owner::getType($ownerId);

		$read = $sharingPrivilegesModule['read'];
		if ($ownerType == 'Users') {
			//Checking the Read Sharing Permission Array in Role Users
			foreach ($read['ROLE'] as $userids) {
				if (in_array($ownerId, $userids)) {
					\App\Log\Log::trace('Exiting isReadPermittedBySharing method ...');
					return true;
				}
			}
			//Checking the Read Sharing Permission Array in Groups Users
			foreach ($read['GROUP'] as $userids) {
				if (in_array($ownerId, $userids)) {
					\App\Log\Log::trace('Exiting isReadPermittedBySharing method ...');
					return true;
				}
			}
		} else {
			if (isset($read['GROUP'][$ownerId])) {
				\App\Log\Log::trace('Exiting isReadPermittedBySharing method ...');
				return true;
			}
		}

		//Checking for the Related Sharing Permission
		$relatedModuleArray = null;
		if (isset($sharingPrivileges['relatedModuleShare'][$tabId])) {
			$relatedModuleArray = $sharingPrivileges['relatedModuleShare'][$tabId];
		}
		if (is_array($relatedModuleArray)) {
			foreach ($relatedModuleArray as $parModId) {
				$parRecordOwner = \App\Security\PrivilegeUtil::getParentRecordOwner($tabId, $parModId, $recordId);
				if (sizeof($parRecordOwner) > 0) {
					$parModName = \App\Utils\ModuleUtils::getModuleName($parModId);
					if (isset($sharingPrivileges['permission'][$parModName . '_' . $moduleName])) {
						$readRelated = $sharingPrivileges['permission'][$parModName . '_' . $moduleName]['read'];

						$relOwnerType = '';
						$relOwnerId = '';
						foreach ($parRecordOwner as $rel_type => $rel_id) {
							$relOwnerType = $rel_type;
							$relOwnerId = $rel_id;
						}
						if ($relOwnerType == 'Users') {
							//Checking in Role Users
							foreach ($readRelated['ROLE'] as $userids) {
								if (in_array($relOwnerId, $userids)) {
									\App\Log\Log::trace('Exiting isReadPermittedBySharing method ...');
									return true;
								}
							}
							//Checking in Group Users
							foreach ($readRelated['GROUP'] as $userids) {
								if (in_array($relOwnerId, $userids)) {
									\App\Log\Log::trace('Exiting isReadPermittedBySharing method ...');
									return true;
								}
							}
						} else {
							if (isset($readRelated['GROUP'][$relOwnerId])) {
								\App\Log\Log::trace('Exiting isReadPermittedBySharing method ...');
								return true;
							}
						}
					}
				}
			}
		}
		\App\Log\Log::trace('Exiting isReadPermittedBySharing method ...');
		return false;
	}

	/** Function to check if the currently logged in user has Write Access due to Sharing for the specified record
	 * @param $moduleName -- Module Name:: Type varchar
	 * @param $actionid -- Action Id:: Type integer
	 * @param $recordid -- Record Id:: Type integer
	 * @param $tabid -- Tab Id:: Type integer
	 * @returns yes or no. If Yes means this action is allowed for the currently logged in user. If no means this action is not allowed for the currently logged in user
	 */
	public static function isReadWritePermittedBySharing($moduleName, $tabId, $actionId, $recordId, $userId)
	{
		$sharingPrivileges = self::getSharingFile($userId);
		if (!isset($sharingPrivileges['permission'][$moduleName])) {
			return false;
		}
		$sharingPrivilegesModule = $sharingPrivileges['permission'][$moduleName];

		$recordMetaData = \vtlib\Functions:: getCRMRecordMetadata($recordId);
		$ownerId = $recordMetaData['smownerid'];
		$ownerType = \App\Fields\Owner::getType($ownerId);

		$write = $sharingPrivilegesModule['write'];
		if ($ownerType == 'Users') {
			//Checking the Write Sharing Permission Array in Role Users
			foreach ($write['ROLE'] as $userids) {
				if (in_array($ownerId, $userids)) {
					\App\Log\Log::trace('Exiting isReadWritePermittedBySharing method ...');
					return true;
				}
			}
			//Checking the Write Sharing Permission Array in Groups Users
			foreach ($write['GROUP'] as $userids) {
				if (in_array($ownerId, $userids)) {
					\App\Log\Log::trace('Exiting isReadWritePermittedBySharing method ...');
					return true;
				}
			}
		} elseif ($ownerType == 'Groups') {
			if (isset($write['GROUP'][$ownerId])) {
				\App\Log\Log::trace('Exiting isReadWritePermittedBySharing method ...');
				return true;
			}
		}
		//Checking for the Related Sharing Permission
		$relatedModuleArray = $sharingPrivileges['relatedModuleShare'][$tabId];
		if (is_array($relatedModuleArray)) {
			foreach ($relatedModuleArray as $parModId) {
				$parRecordOwner = \App\Security\PrivilegeUtil::getParentRecordOwner($tabId, $parModId, $recordId);
				if (!empty($parRecordOwner)) {
					$parModName = \App\Utils\ModuleUtils::getModuleName($parModId);
					if (isset($sharingPrivileges['permission'][$parModName . '_' . $moduleName])) {
						$writeRelated = $sharingPrivileges['permission'][$parModName . '_' . $moduleName]['write'];
						$relOwnerType = '';
						$relOwnerId = '';
						foreach ($parRecordOwner as $rel_type => $rel_id) {
							$relOwnerType = $rel_type;
							$relOwnerId = $rel_id;
						}
						if ($relOwnerType == 'Users') {
							//Checking in Role Users
							foreach ($writeRelated['ROLE'] as $userids) {
								if (in_array($relOwnerId, $userids)) {
									\App\Log\Log::trace('Exiting isReadWritePermittedBySharing method ...');
									return true;
								}
							}
							//Checking in Group Users
							foreach ($writeRelated['GROUP'] as $userids) {
								if (in_array($relOwnerId, $userids)) {
									\App\Log\Log::trace('Exiting isReadWritePermittedBySharing method ...');
									return true;
								}
							}
						} else {
							if (isset($writeRelated['GROUP'][$relOwnerId])) {
								\App\Log\Log::trace('Exiting isReadWritePermittedBySharing method ...');
								return true;
							}
						}
					}
				}
			}
		}
		\App\Log\Log::trace('Exiting isReadWritePermittedBySharing method ...');
		return false;
	}

	/**
	 * Add to global permissions update queue.
	 * @param string $moduleName Module name
	 * @param int $record If type = 1 starting number if type = 0 record ID
	 * @param int $priority
	 * @param int $type
	 */
	public static function setUpdater($moduleName, $record = false, $priority = false, $type = 1)
	{
		\App\Security\PrivilegeUpdater::setUpdater($moduleName, $record, $priority, $type);
	}

	public static function setAllUpdater()
	{
		\App\Security\PrivilegeUpdater::setAllUpdater();
	}
}
