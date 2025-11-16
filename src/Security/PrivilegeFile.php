<?php
namespace App\Security;

/**
 * Privilege File basic class
 * @package YetiForce.App
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */
class PrivilegeFile
{

	protected static $usersFile = 'user_privileges/users.php';
	protected static $usersFileCache = false;

	/**
	 * Create users privileges file
	 */
	public static function createUsersFile()
	{
		$db = \App\Database\PearDatabase::getInstance();
		$entityData = \App\Utils\ModuleUtils::getEntityInfo('Users');
		$result = $db->query('SELECT id,first_name,last_name,is_admin,cal_color,status,email1,user_name,deleted FROM vtiger_users');
		$users = [];
		// Get the id and the name.
		while ($row = $db->getRow($result)) {
			$fullName = '';
			foreach ($entityData['fieldnameArr'] as &$field) {
				$fullName .= ' ' . $row[$field];
			}
			$row['fullName'] = trim($fullName);
			$users['id'][$row['id']] = $row;
			$users['userName'][$row['user_name']] = $row['id'];
		}
		file_put_contents(static::$usersFile, '<?php return ' . \vtlib\Functions:: varExportMin($users) . ';');
	}

	/**
	 * get general users privileges file
	 * @param string $type
	 * @return array
	 */
	public static function getUser($type)
	{
		if (static::$usersFileCache === false) {
			static::$usersFileCache = require static::$usersFile;
		}
		return isset(static::$usersFileCache[$type]) ? static::$usersFileCache[$type] : false;
	}

	/**
	 * Creates a file with all the user, user-role,user-profile, user-groups informations 
	 * @param $userId -- user id:: Type integer
	 */
	public static function createUserPrivilegesFile($userId)
	{
		$file = ROOT_DIRECTORY . DIRECTORY_SEPARATOR . 'user_privileges' . DIRECTORY_SEPARATOR . "user_privileges_$userId.php";
		$user = [];
		$userInstance = \App\Core\CRMEntity::getInstance('Users');
		$userInstance->retrieve_entity_info($userId, 'Users');
		$userInstance->column_fields['is_admin'] = $userInstance->is_admin === 'on';
		$entityData = \App\Utils\ModuleUtils::getEntityInfo('Users');
		$displayName = '';
		foreach ($entityData['fieldnameArr'] as &$field) {
			$displayName .= ' ' . $userInstance->column_fields[$field];
		}
		$userRoleInfo = \App\Security\PrivilegeUtil::getRoleDetail($userInstance->column_fields['roleid']);
		$user['details'] = $userInstance->column_fields;
		$user['displayName'] = trim($displayName);
		$user['profiles'] = \App\Security\PrivilegeUtil::getProfilesByRole($userInstance->column_fields['roleid']);
		$user['groups'] = \App\Security\PrivilegeUtil::getUserGroups($userId);
		$user['parent_roles'] = $userRoleInfo['parentRoles'];
		$user['parent_role_seq'] = $userRoleInfo['parentrole'];
		$profileGlobalPermission = \App\Modules\Users\Models\Privileges::getCombinedUserGlobalPermissions($userId);
		$profileTabsPermission = \App\Modules\Users\Models\Privileges::getCombinedUserTabsPermissions($userId);
		$profileActionPermission = \App\Modules\Users\Models\Privileges::getCombinedUserActionPermissions($userId);
		$subordinateRoles = \App\Security\PrivilegeUtil::getRoleSubordinates($userInstance->column_fields['roleid']);
		$subordinateRolesUsers = \App\Security\PrivilegeUtil::getSubordinateRoleAndUsers($userInstance->column_fields['roleid']);

		$content = "<?php\n";
		$content .= '$is_admin = ' . ($userInstance->column_fields['is_admin'] ? 'true' : 'false') . ";\n";
		$content .= '$user_info = ' . \vtlib\Functions:: varExportMin($userInstance->column_fields) . ";\n";
		$content .= '$current_user_roles = ' . \vtlib\Functions:: varExportMin($userInstance->column_fields['roleid']) . ";\n";
		$content .= '$current_user_parent_role_seq = ' . \vtlib\Functions:: varExportMin($userRoleInfo['parentrole']) . ";\n";
		$content .= '$current_user_profiles = ' . \vtlib\Functions:: varExportMin($user['profiles']) . ";\n";
		$content .= '$profileGlobalPermission = ' . \vtlib\Functions:: varExportMin($profileGlobalPermission) . ";\n";
		$content .= '$profileTabsPermission = ' . \vtlib\Functions:: varExportMin($profileTabsPermission) . ";\n";
		$content .= '$profileActionPermission = ' . \vtlib\Functions:: varExportMin($profileActionPermission) . ";\n";
		$content .= '$current_user_groups = ' . \vtlib\Functions:: varExportMin($user['groups']) . ";\n";
		$content .= '$subordinate_roles = ' . \vtlib\Functions:: varExportMin($subordinateRoles) . ";\n";
		$content .= '$parent_roles = ' . \vtlib\Functions:: varExportMin($userRoleInfo['parentRoles']) . ";\n";
		$content .= '$subordinate_roles_users = ' . \vtlib\Functions:: varExportMin($subordinateRolesUsers) . ";\n";
		$content .= "return " . \vtlib\Functions:: varExportMin($user) . ";\n";
		$result = file_put_contents($file, $content, LOCK_EX);
		if ($result === false) {
			\App\Log\Log::error("Failed to write privilege file for user $userId: $file");
			throw new \Exception("Failed to write privilege file for user $userId");
		}
	}
}
