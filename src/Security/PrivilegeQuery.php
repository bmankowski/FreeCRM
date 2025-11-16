<?php
namespace App\Security;

/**
 * Privilege File basic class
 * @package YetiForce.App
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */
class PrivilegeQuery
{

	public static function getAccessConditions($moduleName, $userId = false, $relatedRecord = false)
	{
		if (!$userId) {
			$userId = \App\Modules\Users\Models\Record::getCurrentUserId();
		}
		$userModel = \App\Modules\Users\Models\Privileges::getInstanceById($userId);
		if ($relatedRecord !== false && \App\Core\AppConfig::security('PERMITTED_BY_RECORD_HIERARCHY')) {
			$role = $userModel->getRoleDetail();
			if ($role->get('listrelatedrecord') == 2) {
				$rparentRecord = \App\Modules\Users\Models\Privileges::getParentRecord($relatedRecord, false, $role->get('listrelatedrecord'));
				if ($rparentRecord) {
					$relatedRecord = $rparentRecord;
				}
			}
			if ($role->get('listrelatedrecord') != 0) {
				$recordMetaData = \vtlib\Functions:: getCRMRecordMetadata($relatedRecord);
				$recordPermission = \App\Security\Privilege::isPermitted($recordMetaData['setype'], 'DetailView', $relatedRecord, $userId);
				if ($recordPermission) {
					return '';
				}
			}
		}
		$query = [];
		$tabId = \App\Utils\ModuleUtils::getModuleId($moduleName);
		$defaultOrgSharingPermission = isset($userModel->defaultOrgSharingPermission[$tabId]) ? $userModel->defaultOrgSharingPermission[$tabId] : null;
		if ($userModel->is_admin === 'off' && $userModel->profile_global_permission[1] == 1 && $userModel->profile_global_permission[2] == 1 && $defaultOrgSharingPermission === 3) {

			$query[] = "vtiger_crmentity.smownerid = '$userId'";
			if (!empty($userModel->groups)) {
				$query[] = 'vtiger_crmentity.smownerid IN (' . implode(',', $userModel->groups) . ')';
			}
			if (\App\Core\AppConfig::security('PERMITTED_BY_ROLES')) {
				$parentRoleSeq = $userModel->parent_role_seq;
				$query[] = "vtiger_crmentity.smownerid IN (SELECT vtiger_user2role.userid AS userid FROM vtiger_user2role INNER JOIN vtiger_users ON vtiger_users.id=vtiger_user2role.userid INNER JOIN vtiger_role ON vtiger_role.roleid=vtiger_user2role.roleid WHERE vtiger_role.parentrole like '$parentRoleSeq::%')";
			}
			if (\App\Core\AppConfig::security('PERMITTED_BY_SHARING')) {
				$sharingPrivileges = \App\Security\Privilege::getSharingFile($userId);
				if (isset($sharingPrivileges['permission'][$moduleName])) {
					$sharingPrivilegesModule = $sharingPrivileges['permission'][$moduleName];
					$sharingRuleInfo = $sharingPrivilegesModule['read'];
					if (!empty($sharingRuleInfo['ROLE'])) {
						$query[] = "vtiger_crmentity.smownerid IN (SELECT shareduserid FROM vtiger_tmp_read_user_sharing_per WHERE userid=$userId && tabid=$tabId)";
					}
					if (!empty($sharingRuleInfo['GROUP'])) {
						$query[] = "vtiger_crmentity.smownerid IN (SELECT vtiger_tmp_read_group_sharing_per.sharedgroupid FROM vtiger_tmp_read_group_sharing_per WHERE userid=$userId && tabid=$tabId)";
					}
				}
			}
			if (\App\Core\AppConfig::security('PERMITTED_BY_SHARED_OWNERS')) {
				$shownerid = array_merge([$userId], $userModel->groups);
				$query[] = 'vtiger_crmentity.crmid IN (SELECT DISTINCT crmid FROM u_yf_crmentity_showners WHERE userid IN (' . implode(',', $shownerid) . '))';
			}
		}
		if (!empty($query)) {
			return ' AND (' . implode(' OR ', $query) . ')';
		}
		return '';
	}

public static function getConditions(\App\Db\Query $query, $moduleName, $user = false, $relatedRecord = false)
{
	if ($user && (is_object($user) && method_exists($user, 'getId'))) {
		$userId = $user->getId();
	} elseif ($user && is_numeric($user)) {
		$userId = $user;
	} else {
		$userId = \App\Modules\Users\Models\Record::getCurrentUserId();
	}
	$userModel = \App\Modules\Users\Models\Privileges::getInstanceById($userId);
		if ($relatedRecord !== false && \App\Core\AppConfig::security('PERMITTED_BY_RECORD_HIERARCHY')) {
			$role = $userModel->getRoleDetail();
			if ($role->get('listrelatedrecord') == 2) {
				$rparentRecord = \App\Modules\Users\Models\Privileges::getParentRecord($relatedRecord, false, $role->get('listrelatedrecord'));
				if ($rparentRecord) {
					$relatedRecord = $rparentRecord;
				}
			}
			if ($role->get('listrelatedrecord') != 0) {
				$recordMetaData = \vtlib\Functions:: getCRMRecordMetadata($relatedRecord);
				$recordPermission = \App\Security\Privilege::isPermitted($recordMetaData['setype'], 'DetailView', $relatedRecord, $userId);
				if ($recordPermission) {
					return '';
				}
			}
		}
		$tabId =\App\Utils\ModuleUtils::getModuleId($moduleName);
		$defaultOrgSharingPermission = isset($userModel->defaultOrgSharingPermission[$tabId]) ? $userModel->defaultOrgSharingPermission[$tabId] : null;
		if (!$userModel->isAdminUser() && $userModel->profile_global_permission[1] == 1 && $userModel->profile_global_permission[2] == 1 && $defaultOrgSharingPermission === 3) {
			$conditions = ['or'];
			$conditions[] = ['vtiger_crmentity.smownerid' => $userId];
			if (!empty($userModel->groups)) {
				$conditions[] = ['vtiger_crmentity.smownerid' => $userModel->groups];
			}
			if (\App\Core\AppConfig::security('PERMITTED_BY_ROLES')) {
				$parentRoleSeq = $userModel->parent_role_seq;
				$subQuery = (new \App\Db\Query())->select('userid')
					->from('vtiger_user2role')
					->innerJoin('vtiger_users', 'vtiger_user2role.userid = vtiger_users.id')
					->innerJoin('vtiger_role', 'vtiger_user2role.roleid = vtiger_role.roleid')
					->where(['like', 'vtiger_role.parentrole', "$parentRoleSeq::%", false]);
				$conditions[] = ['vtiger_crmentity.smownerid' => $subQuery];
			}
			if (\App\Core\AppConfig::security('PERMITTED_BY_SHARING')) {
				$sharingPrivileges = \App\Security\Privilege::getSharingFile($userId);
				if (isset($sharingPrivileges['permission'][$moduleName])) {
					$sharingPrivilegesModule = $sharingPrivileges['permission'][$moduleName];
					$sharingRuleInfo = $sharingPrivilegesModule['read'];
					if (!empty($sharingRuleInfo['ROLE'])) {
						$subQuery = (new \App\Db\Query())->select('shareduserid')
							->from('vtiger_tmp_read_user_sharing_per')
							->where(['userid' => $userId, 'tabid' => $tabId]);
						$conditions[] = ['vtiger_crmentity.smownerid' => $subQuery];
					}
					if (!empty($sharingRuleInfo['GROUP'])) {
						$subQuery = (new \App\Db\Query())->select('sharedgroupid')
							->from('vtiger_tmp_read_group_sharing_per')
							->where(['userid' => $userId, 'tabid' => $tabId]);
						$conditions[] = ['vtiger_crmentity.smownerid' => $subQuery];
					}
				}
			}
			if (\App\Core\AppConfig::security('PERMITTED_BY_SHARED_OWNERS')) {
				$subQuery = (new \App\Db\Query())->select('crmid')->distinct()
					->from('u_yf_crmentity_showners')
					->where(['userid' => array_merge([$userId], $userModel->groups)]);
				$conditions[] = ['vtiger_crmentity.crmid' => $subQuery];
			}
			if (!empty($conditions)) {
				$query->andWhere($conditions);
			}
		}
	}

	/**
	 * Function returns non admin access control check query
	 * @param string $module
	 * @param object $user
	 * @param string $scope
	 * @return string
	 */
	public static function getNonAdminAccessControlQuery($module, $user, $scope = '')
	{
		$instance = \App\Core\CRMEntity::getInstance($module);
		return $instance->getNonAdminAccessControlQuery($module, $user, $scope);
	}

	/**
	 * Function to get list view security parameter for a module
	 * @param string $module Module name
	 * @return string SQL security query string
	 */
	public static function getListViewSecurityParameter($module)
	{
		\App\Log\Log::trace("Entering getListViewSecurityParameter(" . $module . ") method ...");
		$adb = \App\Database\PearDatabase::getInstance();

		$tabid = \App\Utils\ModuleUtils::getModuleId($module);
		$currentUser = \App\User\CurrentUser::get();
		$sec_query = '';
		require('user_privileges/user_privileges_' . $currentUser->getId() . '.php');
		require('user_privileges/sharing_privileges_' . $currentUser->getId() . '.php');
		
		if ($module == 'Leads') {
			$sec_query .= " and (
						vtiger_crmentity.smownerid in(" . $currentUser->getId() . ")
						or vtiger_crmentity.smownerid in(select vtiger_user2role.userid from vtiger_user2role inner join vtiger_users on vtiger_users.id=vtiger_user2role.userid inner join vtiger_role on vtiger_role.roleid=vtiger_user2role.roleid where vtiger_role.parentrole like '" . $current_user_parent_role_seq . "::%')
						or vtiger_crmentity.smownerid in(select shareduserid from vtiger_tmp_read_user_sharing_per where userid=" . $currentUser->getId() . " and tabid=" . $tabid . ")
						or (";

			if (isset($current_user_groups) && sizeof($current_user_groups) > 0) {
				$sec_query .= " vtiger_groups.groupid in (" . implode(",", $current_user_groups) . ") or ";
			}
			$sec_query .= " vtiger_groups.groupid in(select vtiger_tmp_read_group_sharing_per.sharedgroupid from vtiger_tmp_read_group_sharing_per where userid=" . $currentUser->getId() . " and tabid=" . $tabid . "))) ";
		} elseif ($module == 'Accounts') {
			$sec_query .= " and (vtiger_crmentity.smownerid in(" . $currentUser->getId() . ") " .
				"or vtiger_crmentity.smownerid in(select vtiger_user2role.userid from vtiger_user2role inner join vtiger_users on vtiger_users.id=vtiger_user2role.userid inner join vtiger_role on vtiger_role.roleid=vtiger_user2role.roleid where vtiger_role.parentrole like '" . $current_user_parent_role_seq . "::%') " .
				"or vtiger_crmentity.smownerid in(select shareduserid from vtiger_tmp_read_user_sharing_per where userid=" . $currentUser->getId() . " and tabid=" . $tabid . ") or (";

			if (isset($current_user_groups) && sizeof($current_user_groups) > 0) {
				$sec_query .= " vtiger_groups.groupid in (" . implode(",", $current_user_groups) . ") or ";
			}
			$sec_query .= " vtiger_groups.groupid in(select vtiger_tmp_read_group_sharing_per.sharedgroupid from vtiger_tmp_read_group_sharing_per where userid=" . $currentUser->getId() . " and tabid=" . $tabid . "))) ";
		} elseif ($module == 'Contacts') {
			$sec_query .= " and (vtiger_crmentity.smownerid in(" . $currentUser->getId() . ") " .
				"or vtiger_crmentity.smownerid in(select vtiger_user2role.userid from vtiger_user2role inner join vtiger_users on vtiger_users.id=vtiger_user2role.userid inner join vtiger_role on vtiger_role.roleid=vtiger_user2role.roleid where vtiger_role.parentrole like '" . $current_user_parent_role_seq . "::%') " .
				"or vtiger_crmentity.smownerid in(select shareduserid from vtiger_tmp_read_user_sharing_per where userid=" . $currentUser->getId() . " and tabid=" . $tabid . ") or (";

			if (isset($current_user_groups) && sizeof($current_user_groups) > 0) {
				$sec_query .= " vtiger_groups.groupid in (" . implode(",", $current_user_groups) . ") or ";
			}
			$sec_query .= " vtiger_groups.groupid in(select vtiger_tmp_read_group_sharing_per.sharedgroupid from vtiger_tmp_read_group_sharing_per where userid=" . $currentUser->getId() . " and tabid=" . $tabid . "))) ";
		} elseif ($module == 'HelpDesk') {
			$sec_query .= " and (vtiger_crmentity.smownerid in(" . $currentUser->getId() . ") or vtiger_crmentity.smownerid in(select vtiger_user2role.userid from vtiger_user2role inner join vtiger_users on vtiger_users.id=vtiger_user2role.userid inner join vtiger_role on vtiger_role.roleid=vtiger_user2role.roleid where vtiger_role.parentrole like '" . $current_user_parent_role_seq . "::%') or vtiger_crmentity.smownerid in(select shareduserid from vtiger_tmp_read_user_sharing_per where userid=" . $currentUser->getId() . " and tabid=" . $tabid . ") ";

			$sec_query .= " or (";
			if (isset($current_user_groups) && sizeof($current_user_groups) > 0) {
				$sec_query .= " vtiger_groups.groupid in (" . implode(",", $current_user_groups) . ") or ";
			}
			$sec_query .= " vtiger_groups.groupid in(select vtiger_tmp_read_group_sharing_per.sharedgroupid from vtiger_tmp_read_group_sharing_per where userid=" . $currentUser->getId() . " and tabid=" . $tabid . "))) ";
		} elseif ($module === 'Calendar') {
			$sec_query .= " and (vtiger_crmentity.smownerid in(" . $currentUser->getId() . ") or vtiger_crmentity.smownerid in(select vtiger_user2role.userid from vtiger_user2role inner join vtiger_users on vtiger_users.id=vtiger_user2role.userid inner join vtiger_role on vtiger_role.roleid=vtiger_user2role.roleid where vtiger_role.parentrole like '" . $current_user_parent_role_seq . "::%')";

			if (isset($current_user_groups) && sizeof($current_user_groups) > 0) {
				$sec_query .= " or ((vtiger_groups.groupid in (" . implode(",", $current_user_groups) . ")))";
			}
			$sec_query .= ")";
		} elseif ($module === 'Campaigns') {
			$sec_query .= " and (vtiger_crmentity.smownerid in(" . $currentUser->getId() . ") or vtiger_crmentity.smownerid in(select vtiger_user2role.userid from vtiger_user2role inner join vtiger_users on vtiger_users.id=vtiger_user2role.userid inner join vtiger_role on vtiger_role.roleid=vtiger_user2role.roleid where vtiger_role.parentrole like '" . $current_user_parent_role_seq . "::%') or vtiger_crmentity.smownerid in(select shareduserid from vtiger_tmp_read_user_sharing_per where userid=" . $currentUser->getId() . " and tabid=" . $tabid . ") or ((";

			if (isset($current_user_groups) && sizeof($current_user_groups) > 0) {
				$sec_query .= " vtiger_groups.groupid in (" . implode(",", $current_user_groups) . ") or ";
			}
			$sec_query .= " vtiger_groups.groupid in(select vtiger_tmp_read_group_sharing_per.sharedgroupid from vtiger_tmp_read_group_sharing_per where userid=" . $currentUser->getId() . " and tabid=" . $tabid . ")))) ";
		} elseif ($module == 'Documents') {
			$sec_query .= " and (vtiger_crmentity.smownerid in(" . $currentUser->getId() . ") or vtiger_crmentity.smownerid in(select vtiger_user2role.userid from vtiger_user2role inner join vtiger_users on vtiger_users.id=vtiger_user2role.userid inner join vtiger_role on vtiger_role.roleid=vtiger_user2role.roleid where vtiger_role.parentrole like '" . $current_user_parent_role_seq . "::%') or vtiger_crmentity.smownerid in(select shareduserid from vtiger_tmp_read_user_sharing_per where userid=" . $currentUser->getId() . " and tabid=" . $tabid . ") or ((";

			if (isset($current_user_groups) && sizeof($current_user_groups) > 0) {
				$sec_query .= " vtiger_groups.groupid in (" . implode(",", $current_user_groups) . ") or ";
			}
			$sec_query .= " vtiger_groups.groupid in(select vtiger_tmp_read_group_sharing_per.sharedgroupid from vtiger_tmp_read_group_sharing_per where userid=" . $currentUser->getId() . " and tabid=" . $tabid . ")))) ";
		} elseif ($module == 'Products') {
			$sec_query .= " and (vtiger_crmentity.smownerid in(" . $currentUser->getId() . ") " .
				"or vtiger_crmentity.smownerid in(select vtiger_user2role.userid from vtiger_user2role inner join vtiger_users on vtiger_users.id=vtiger_user2role.userid inner join vtiger_role on vtiger_role.roleid=vtiger_user2role.roleid where vtiger_role.parentrole like '" . $current_user_parent_role_seq . "::%') " .
				"or vtiger_crmentity.smownerid in(select shareduserid from vtiger_tmp_read_user_sharing_per where userid=" . $currentUser->getId() . " and tabid=" . $tabid . ")";

			$sec_query .= " or (";
			if (isset($current_user_groups) && sizeof($current_user_groups) > 0) {
				$sec_query .= " vtiger_groups.groupid in (" . implode(",", $current_user_groups) . ") or ";
			}
			$sec_query .= " vtiger_groups.groupid in(select vtiger_tmp_read_group_sharing_per.sharedgroupid from vtiger_tmp_read_group_sharing_per where userid=" . $currentUser->getId() . " and tabid=" . $tabid . "))) ";
		} else {
			$modObj = \App\Core\CRMEntity::getInstance($module);
			$sec_query = $modObj->getListViewSecurityParameter($module);
		}
		\App\Log\Log::trace("Exiting getListViewSecurityParameter method ...");
		return $sec_query;
	}
}
