<?php

namespace App\Modules\Users\Services;

use App\Core\CRMEntity;
use App\Database\PearDatabase;
use App\Utils\ModuleUtils;
use App\PrivilegeFile;
use App\PrivilegeUtil;
use App\Utils\GetGroupUsers;
use App\Modules\Users\Models\Privileges;
use App\Modules\Users\Models\Record;
use vtlib\Functions;

/**
 * Privilege File Manager Service
 * 
 * This class manages the creation and maintenance of user privilege files
 * and sharing privilege files for the FreeCRM system.
 * 
 * @package App\Modules\Users\Services
 */
class PrivilegeFileManager
{
    /**
     * Creates a file with all the user, user-role, user-profile, user-groups informations
     * 
     * @param int $userId User ID
     * @return bool True on success, false on failure
     */
    public static function createUserPrivilegesFile($userId): bool
    {
        $handle = @fopen(ROOT_DIRECTORY . DIRECTORY_SEPARATOR . 'user_privileges/user_privileges_' . $userId . '.php', "w+");

        if ($handle) {
            $newbuf = '';
            $newbuf .= "<?php\n";
            $user_focus = CRMEntity::getInstance('Users');
            $user_focus->retrieve_entity_info($userId, 'Users');
            $userInfo = [];
            $user_focus->column_fields["id"] = '';
            $user_focus->id = $userId;
            foreach ($user_focus->column_fields as $field => $value_iter) {
                if (isset($user_focus->$field)) {
                    $userInfo[$field] = $user_focus->$field;
                }
            }
            if ($user_focus->is_admin == 'on') {
                $newbuf .= "\$is_admin=true;\n";
                $newbuf .= "\$user_info=" . Functions::varExportMin($userInfo) . ";\n";
            } else {
                $newbuf .= "\$is_admin=false;\n";

                $globalPermissionArr = Privileges::getCombinedUserGlobalPermissions($userId);
                $tabsPermissionArr = Privileges::getCombinedUserTabsPermissions($userId);
                $actionPermissionArr = Privileges::getCombinedUserActionPermissions($userId);
                $user_role = \App\Security\PrivilegeUtil::getRoleByUsers($userId);
                $user_role_info = \App\Security\PrivilegeUtil::getRoleDetail($user_role);
                $user_role_parent = $user_role_info['parentrole'];
                $subRoles = \App\Security\PrivilegeUtil::getRoleSubordinates($user_role);
                $subRoleAndUsers = \App\Security\PrivilegeUtil::getSubordinateRoleAndUsers($user_role);
                $parentRoles = \App\Security\PrivilegeUtil::getParentRole($user_role);
                $newbuf .= "\$current_user_roles='" . $user_role . "';\n";
                $newbuf .= "\$current_user_parent_role_seq='" . $user_role_parent . "';\n";
                $newbuf .= "\$current_user_profiles=" . self::constructSingleArray(\App\Security\PrivilegeUtil::getProfilesByRole($user_role)) . ";\n";
                $newbuf .= "\$profileGlobalPermission=" . self::constructArray($globalPermissionArr) . ";\n";
                $newbuf .= "\$profileTabsPermission=" . self::constructArray($tabsPermissionArr) . ";\n";
                $newbuf .= "\$profileActionPermission=" . self::constructTwoDimensionalArray($actionPermissionArr) . ";\n";
                $newbuf .= "\$current_user_groups=" . self::constructSingleArray(\App\Modules\Base\Helpers\Util::getGroupsIdsForUsers($userId)) . ";\n";
                $newbuf .= "\$subordinate_roles=" . self::constructSingleCharArray($subRoles) . ";\n";
                $newbuf .= "\$parent_roles=" . self::constructSingleCharArray($parentRoles) . ";\n";
                $newbuf .= "\$subordinate_roles_users=" . self::constructTwoDimensionalCharIntSingleArray($subRoleAndUsers) . ";\n";
                $newbuf .= "\$user_info=" . Functions::varExportMin($userInfo) . ";\n";
            }
            fputs($handle, $newbuf);
            fclose($handle);
            \App\Security\PrivilegeFile::createUserPrivilegesFile($userId);
            Privileges::clearCache($userId);
            \App\Modules\Users\Models\Record::clearCache($userId);
            return true;
        }
        return false;
    }

    /**
     * Creates a file with all the organization default sharing permissions and custom sharing permissions specific for the specified user
     * 
     * @param int $userId User ID
     * @return bool True on success, false on failure
     */
    public static function createUserSharingPrivilegesFile($userId): bool
    {
		\vtlib\Deprecated::checkFileAccessForInclusion('user_privileges/user_privileges_' . $userId . '.php');
		$userPrivilegesData = require 'user_privileges/user_privileges_' . $userId . '.php';
		if (is_array($userPrivilegesData)) {
			if (!isset($current_user_roles)) {
				$current_user_roles = $userPrivilegesData['details']['roleid'] ?? '';
			}
			if (!isset($parent_roles)) {
				$parent_roles = $userPrivilegesData['parent_roles'] ?? [];
			}
			if (!isset($current_user_groups)) {
				$current_user_groups = $userPrivilegesData['groups'] ?? [];
			}
		}
        $handle = @fopen(ROOT_DIRECTORY . DIRECTORY_SEPARATOR . 'user_privileges/sharing_privileges_' . $userId . '.php', "w+");

        if ($handle) {
            $newbuf = "<?php\n";
            $user_focus = CRMEntity::getInstance('Users');
            $user_focus->retrieve_entity_info($userId, 'Users');
            if ($user_focus->is_admin == 'on') {
                fputs($handle, $newbuf);
                fclose($handle);
                return true;
            } else {
                $sharingPrivileges = [];
                //Constructing the Default Org Share Array
                $def_org_share = \App\Security\PrivilegeUtil::getAllDefaultSharingAction();
                $newbuf .= "\$defaultOrgSharingPermission=" . self::constructArray($def_org_share) . ";\n";
                $sharingPrivileges['defOrgShare'] = $def_org_share;

                $relatedModuleShare = \App\Security\PrivilegeUtil::getDatashareRelatedModules();
                $newbuf .= "\$related_module_share=" . self::constructTwoDimensionalValueArray($relatedModuleShare) . ";\n";
                $sharingPrivileges['relatedModuleShare'] = $relatedModuleShare;
                //Constructing Account Sharing Rules
                $account_share_per_array = \App\Security\PrivilegeUtil::getUserModuleSharingObjects('Accounts', $userId, $def_org_share, $current_user_roles, $parent_roles, $current_user_groups);
                $account_share_read_per = $account_share_per_array['read'];
                $account_share_write_per = $account_share_per_array['write'];
                $account_sharingrule_members = $account_share_per_array['sharingrules'];
                $newbuf .= "\$Accounts_share_read_permission=array('ROLE'=>" . self::constructTwoDimensionalCharIntSingleValueArray($account_share_read_per['ROLE']) . ",'GROUP'=>" . self::constructTwoDimensionalValueArray($account_share_read_per['GROUP']) . ");\n";
                $newbuf .= "\$Accounts_share_write_permission=array('ROLE'=>" . self::constructTwoDimensionalCharIntSingleValueArray($account_share_write_per['ROLE']) . ",'GROUP'=>" . self::constructTwoDimensionalValueArray($account_share_write_per['GROUP']) . ");\n";
                $sharingPrivileges['permission']['Accounts'] = ['read' => $account_share_read_per, 'write' => $account_share_write_per];
                //Constructing Contact Sharing Rules
                $newbuf .= "\$Contacts_share_read_permission=array('ROLE'=>" . self::constructTwoDimensionalCharIntSingleValueArray($account_share_read_per['ROLE']) . ",'GROUP'=>" . self::constructTwoDimensionalValueArray($account_share_read_per['GROUP']) . ");\n";
                $newbuf .= "\$Contacts_share_write_permission=array('ROLE'=>" . self::constructTwoDimensionalCharIntSingleValueArray($account_share_write_per['ROLE']) . ",'GROUP'=>" . self::constructTwoDimensionalValueArray($account_share_write_per['GROUP']) . ");\n";
                $sharingPrivileges['permission']['Contacts'] = ['read' => $account_share_read_per, 'write' => $account_share_write_per];

                //Constructing the Account Ticket Related Module Sharing Array
                $acct_related_tkt = self::getRelatedModuleSharingArray('Accounts', 'HelpDesk', $account_sharingrule_members, $account_share_read_per, $account_share_write_per, $def_org_share);
                $acc_tkt_share_read_per = $acct_related_tkt['read'];
                $acc_tkt_share_write_per = $acct_related_tkt['write'];
                $newbuf .= "\$Accounts_HelpDesk_share_read_permission=array('ROLE'=>" . self::constructTwoDimensionalCharIntSingleValueArray($acc_tkt_share_read_per['ROLE']) . ",'GROUP'=>" . self::constructTwoDimensionalValueArray($acc_tkt_share_read_per['GROUP']) . ");\n";
                $newbuf .= "\$Accounts_HelpDesk_share_write_permission=array('ROLE'=>" . self::constructTwoDimensionalCharIntSingleValueArray($acc_tkt_share_write_per['ROLE']) . ",'GROUP'=>" . self::constructTwoDimensionalValueArray($acc_tkt_share_write_per['GROUP']) . ");\n";
                $sharingPrivileges['permission']['Accounts_HelpDesk'] = ['read' => $acc_tkt_share_read_per, 'write' => $acc_tkt_share_write_per];

                $custom_modules = \App\Utils\ModuleUtils::getSharingModuleList(['Accounts', 'Contacts']);
                foreach ($custom_modules as &$module_name) {
                    $mod_share_perm_array = \App\Security\PrivilegeUtil::getUserModuleSharingObjects($module_name, $userId, $def_org_share, $current_user_roles, $parent_roles, $current_user_groups);

                    $mod_share_read_perm = $mod_share_perm_array['read'];
                    $mod_share_write_perm = $mod_share_perm_array['write'];
                    $newbuf .= '$' . $module_name . "_share_read_permission=['ROLE'=>" .
                        self::constructTwoDimensionalCharIntSingleValueArray($mod_share_read_perm['ROLE']) . ",'GROUP'=>" .
                        self::constructTwoDimensionalArray($mod_share_read_perm['GROUP']) . "];\n";
                    $newbuf .= '$' . $module_name . "_share_write_permission=['ROLE'=>" .
                        self::constructTwoDimensionalCharIntSingleValueArray($mod_share_write_perm['ROLE']) . ",'GROUP'=>" .
                        self::constructTwoDimensionalArray($mod_share_write_perm['GROUP']) . "];\n";

                    $sharingPrivileges['permission'][$module_name] = ['read' => $mod_share_read_perm, 'write' => $mod_share_write_perm];
                }
                $newbuf .= 'return ' . Functions::varExportMin($sharingPrivileges) . ";\n";
                // END
                fputs($handle, $newbuf);
                fclose($handle);

                //Populating Temp Tables
                self::populateSharingtmptables($userId);
                return true;
            }
        }
        return false;
    }

    /**
     * Gives an array which contains the information for what all roles, groups and user's related module data that is to be shared for the specified parent module and shared module
     * 
     * @param string $parentModule Parent module name
     * @param string $shareModule Shared module name
     * @param array $modSharingruleMembers Sharing Rule Members array
     * @param array $modShareReadPer Sharing Module Read Permission array
     * @param array $modShareWritePer Sharing Module Write Permission array
     * @param array $defOrgShare Default organization sharing permission array
     * @return array Array which contains the id of roles, group and users related module data to be shared
     */
    public static function getRelatedModuleSharingArray($parentModule, $shareModule, $modSharingruleMembers, $modShareReadPer, $modShareWritePer, $defOrgShare): array
    {
        $adb = PearDatabase::getInstance();
        $related_mod_sharing_permission = [];
        $mod_share_read_permission = [];
        $mod_share_write_permission = [];

        $mod_share_read_permission['ROLE'] = [];
        $mod_share_write_permission['ROLE'] = [];
        $mod_share_read_permission['GROUP'] = [];
        $mod_share_write_permission['GROUP'] = [];

        $par_mod_id = \App\Utils\ModuleUtils::getModuleId($parentModule);
        $share_mod_id = \App\Utils\ModuleUtils::getModuleId($shareModule);

        if ($defOrgShare[$share_mod_id] == 3 || $defOrgShare[$share_mod_id] == 0) {
            $role_read_per = [];
            $role_write_per = [];
            $grp_read_per = [];
            $grp_write_per = [];

            foreach ($modSharingruleMembers as $sharingid => $sharingInfoArr) {
                $query = "select vtiger_datashare_relatedmodule_permission.* from vtiger_datashare_relatedmodule_permission inner join vtiger_datashare_relatedmodules on vtiger_datashare_relatedmodules.datashare_relatedmodule_id=vtiger_datashare_relatedmodule_permission.datashare_relatedmodule_id where vtiger_datashare_relatedmodule_permission.shareid=? and vtiger_datashare_relatedmodules.tabid=? and vtiger_datashare_relatedmodules.relatedto_tabid=?";
                $result = $adb->pquery($query, array($sharingid, $par_mod_id, $share_mod_id));
                $share_permission = $adb->query_result($result, 0, 'permission');

                foreach ($sharingInfoArr as $shareType => $shareEntArr) {
                    foreach ($shareEntArr as $key => $shareEntId) {
                        if ($shareType == 'ROLE') {
                            if ($share_permission == 1) {
                                if ($defOrgShare[$share_mod_id] == 3) {
                                    if (!array_key_exists($shareEntId, $role_read_per)) {
                                        if (array_key_exists($shareEntId, $modShareReadPer['ROLE'])) {
                                            $share_role_users = $modShareReadPer['ROLE'][$shareEntId];
                                        } elseif (array_key_exists($shareEntId, $modShareWritePer['ROLE'])) {
                                            $share_role_users = $modShareWritePer['ROLE'][$shareEntId];
                                        } else {
                                            $share_role_users = \App\Security\PrivilegeUtil::getUsersByRole($shareEntId);
                                        }
                                        $role_read_per[$shareEntId] = $share_role_users;
                                    }
                                }
                                if (!array_key_exists($shareEntId, $role_write_per)) {
                                    if (array_key_exists($shareEntId, $modShareReadPer['ROLE'])) {
                                        $share_role_users = $modShareReadPer['ROLE'][$shareEntId];
                                    } elseif (array_key_exists($shareEntId, $modShareWritePer['ROLE'])) {
                                        $share_role_users = $modShareWritePer['ROLE'][$shareEntId];
                                    } else {
                                        $share_role_users = \App\Security\PrivilegeUtil::getUsersByRole($shareEntId);
                                    }
                                    $role_write_per[$shareEntId] = $share_role_users;
                                }
                            } elseif ($share_permission == 0 && $defOrgShare[$share_mod_id] == 3) {
                                if (!array_key_exists($shareEntId, $role_read_per)) {
                                    if (array_key_exists($shareEntId, $modShareReadPer['ROLE'])) {
                                        $share_role_users = $modShareReadPer['ROLE'][$shareEntId];
                                    } elseif (array_key_exists($shareEntId, $modShareWritePer['ROLE'])) {
                                        $share_role_users = $modShareWritePer['ROLE'][$shareEntId];
                                    } else {
                                        $share_role_users = \App\Security\PrivilegeUtil::getUsersByRole($shareEntId);
                                    }
                                    $role_read_per[$shareEntId] = $share_role_users;
                                }
                            }
                        } elseif ($shareType == 'GROUP') {
                            if ($share_permission == 1) {
                                if ($defOrgShare[$share_mod_id] == 3) {
                                    if (!array_key_exists($shareEntId, $grp_read_per)) {
                                        if (array_key_exists($shareEntId, $modShareReadPer['GROUP'])) {
                                            $share_grp_users = $modShareReadPer['GROUP'][$shareEntId];
                                        } elseif (array_key_exists($shareEntId, $modShareWritePer['GROUP'])) {
                                            $share_grp_users = $modShareWritePer['GROUP'][$shareEntId];
                                        } else {
                                            $focusGrpUsers = new GetGroupUsers();
                                            $focusGrpUsers->getAllUsersInGroup($shareEntId);
                                            $share_grp_users = $focusGrpUsers->group_users;

                                            foreach ($focusGrpUsers->group_subgroups as $subgrpid => $subgrpusers) {
                                                if (!array_key_exists($subgrpid, $grp_read_per)) {
                                                    $grp_read_per[$subgrpid] = $subgrpusers;
                                                }
                                            }
                                        }
                                        $grp_read_per[$shareEntId] = $share_grp_users;
                                    }
                                }
                                if (!array_key_exists($shareEntId, $grp_write_per)) {
                                    if (!array_key_exists($shareEntId, $grp_write_per)) {
                                        if (array_key_exists($shareEntId, $modShareReadPer['GROUP'])) {
                                            $share_grp_users = $modShareReadPer['GROUP'][$shareEntId];
                                        } elseif (array_key_exists($shareEntId, $modShareWritePer['GROUP'])) {
                                            $share_grp_users = $modShareWritePer['GROUP'][$shareEntId];
                                        } else {
                                            $focusGrpUsers = new GetGroupUsers();
                                            $focusGrpUsers->getAllUsersInGroup($shareEntId);
                                            $share_grp_users = $focusGrpUsers->group_users;
                                            foreach ($focusGrpUsers->group_subgroups as $subgrpid => $subgrpusers) {
                                                if (!array_key_exists($subgrpid, $grp_write_per)) {
                                                    $grp_write_per[$subgrpid] = $subgrpusers;
                                                }
                                            }
                                        }
                                        $grp_write_per[$shareEntId] = $share_grp_users;
                                    }
                                }
                            } elseif ($share_permission == 0 && $defOrgShare[$share_mod_id] == 3) {
                                if (!array_key_exists($shareEntId, $grp_read_per)) {
                                    if (array_key_exists($shareEntId, $modShareReadPer['GROUP'])) {
                                        $share_grp_users = $modShareReadPer['GROUP'][$shareEntId];
                                    } elseif (array_key_exists($shareEntId, $modShareWritePer['GROUP'])) {
                                        $share_grp_users = $modShareWritePer['GROUP'][$shareEntId];
                                    } else {
                                        $focusGrpUsers = new GetGroupUsers();
                                        $focusGrpUsers->getAllUsersInGroup($shareEntId);
                                        $share_grp_users = $focusGrpUsers->group_users;
                                        foreach ($focusGrpUsers->group_subgroups as $subgrpid => $subgrpusers) {
                                            if (!array_key_exists($subgrpid, $grp_read_per)) {
                                                $grp_read_per[$subgrpid] = $subgrpusers;
                                            }
                                        }
                                    }
                                    $grp_read_per[$shareEntId] = $share_grp_users;
                                }
                            }
                        }
                    }
                }
            }
            $mod_share_read_permission['ROLE'] = $role_read_per;
            $mod_share_write_permission['ROLE'] = $role_write_per;
            $mod_share_read_permission['GROUP'] = $grp_read_per;
            $mod_share_write_permission['GROUP'] = $grp_write_per;
        }
        $related_mod_sharing_permission['read'] = $mod_share_read_permission;
        $related_mod_sharing_permission['write'] = $mod_share_write_permission;
        return $related_mod_sharing_permission;
    }

    /**
     * Function to populate the read/write Sharing permissions data of user/groups for the specified user into the database
     * 
     * @param int $userId User ID
     */
    public static function populateSharingtmptables($userId): void
    {
        $adb = PearDatabase::getInstance();
        \vtlib\Deprecated::checkFileAccessForInclusion('user_privileges/sharing_privileges_' . $userId . '.php');
        require('user_privileges/sharing_privileges_' . $userId . '.php');
        //Deleting from the existing vtiger_tables
        $table_arr = Array('vtiger_tmp_read_user_sharing_per', 'vtiger_tmp_write_user_sharing_per', 'vtiger_tmp_read_group_sharing_per', 'vtiger_tmp_write_group_sharing_per', 'vtiger_tmp_read_user_rel_sharing_per', 'vtiger_tmp_write_user_rel_sharing_per', 'vtiger_tmp_read_group_rel_sharing_per', 'vtiger_tmp_write_group_rel_sharing_per');
        foreach ($table_arr as $tabname) {
            $adb->delete($tabname, 'userid = ?', [$userId]);
        }

        // Look up for modules for which sharing access is enabled.
        $modules = Functions::getAllModules(true, true, 0, false, 0);
        $sharingArray = array_column($modules, 'name');
        foreach ($sharingArray as $module) {
            $module_sharing_read_permvar = $module . '_share_read_permission';
            $module_sharing_write_permvar = $module . '_share_write_permission';

            self::populateSharingPrivileges('USER', $userId, $module, 'read', $$module_sharing_read_permvar);
            self::populateSharingPrivileges('USER', $userId, $module, 'write', $$module_sharing_write_permvar);
            self::populateSharingPrivileges('GROUP', $userId, $module, 'read', $$module_sharing_read_permvar);
            self::populateSharingPrivileges('GROUP', $userId, $module, 'write', $$module_sharing_write_permvar);
        }
        //Populating Values into the temp related sharing tables
        foreach ($related_module_share as $rel_tab_id => $tabid_arr) {
            $rel_tab_name = \App\Utils\ModuleUtils::getModuleName($rel_tab_id);
            if (!empty($rel_tab_name)) {
                foreach ($tabid_arr as $taid) {
                    $tab_name = \App\Utils\ModuleUtils::getModuleName($taid);

                    $relmodule_sharing_read_permvar = $tab_name . '_' . $rel_tab_name . '_share_read_permission';
                    $relmodule_sharing_write_permvar = $tab_name . '_' . $rel_tab_name . '_share_write_permission';

                    self::populateRelatedSharingPrivileges('USER', $userId, $tab_name, $rel_tab_name, 'read', $$relmodule_sharing_read_permvar);
                    self::populateRelatedSharingPrivileges('USER', $userId, $tab_name, $rel_tab_name, 'write', $$relmodule_sharing_write_permvar);
                    self::populateRelatedSharingPrivileges('GROUP', $userId, $tab_name, $rel_tab_name, 'read', $$relmodule_sharing_read_permvar);
                    self::populateRelatedSharingPrivileges('GROUP', $userId, $tab_name, $rel_tab_name, 'write', $$relmodule_sharing_write_permvar);
                }
            }
        }
    }

    /**
     * Function to populate the read/write Sharing permissions data for the specified user into the database
     * 
     * @param string $enttype Can have the value of User or Group
     * @param int $userId User ID
     * @param string $module Module name
     * @param string $pertype Can have the value of read or write
     * @param array|false $varNameArr Variable to use instead of including the sharing access again
     */
    public static function populateSharingPrivileges($enttype, $userId, $module, $pertype, $varNameArr = false): void
    {
        $adb = PearDatabase::getInstance();
        $tabid = \App\Utils\ModuleUtils::getModuleId($module);

        if (!$varNameArr) {
            \vtlib\Deprecated::checkFileAccessForInclusion('user_privileges/sharing_privileges_' . $userId . '.php');
            require('user_privileges/sharing_privileges_' . $userId . '.php');
        }

        if ($enttype == 'USER') {
            if ($pertype == 'read') {
                $table_name = 'vtiger_tmp_read_user_sharing_per';
                $var_name = $module . '_share_read_permission';
            } elseif ($pertype == 'write') {
                $table_name = 'vtiger_tmp_write_user_sharing_per';
                $var_name = $module . '_share_write_permission';
            }
            // Lookup for the variable if not set through function argument		
            if (!$varNameArr)
                $varNameArr = $$var_name;
            $user_arr = [];
            if (sizeof($varNameArr['ROLE']) > 0) {
                foreach ($varNameArr['ROLE'] as $roleid => $roleusers) {
                    foreach ($roleusers as $user_id) {
                        if (!in_array($user_id, $user_arr)) {
                            $query = "insert into " . $table_name . " values(?,?,?)";
                            $adb->pquery($query, array($userId, $tabid, $user_id));
                            $user_arr[] = $user_id;
                        }
                    }
                }
            }
            if (sizeof($varNameArr['GROUP']) > 0) {
                foreach ($varNameArr['GROUP'] as $grpid => $grpusers) {
                    foreach ($grpusers as $user_id) {
                        if (!in_array($user_id, $user_arr)) {
                            $query = "insert into " . $table_name . " values(?,?,?)";
                            $adb->pquery($query, array($userId, $tabid, $user_id));
                            $user_arr[] = $user_id;
                        }
                    }
                }
            }
        } elseif ($enttype == 'GROUP') {
            if ($pertype == 'read') {
                $table_name = 'vtiger_tmp_read_group_sharing_per';
                $var_name = $module . '_share_read_permission';
            } elseif ($pertype == 'write') {
                $table_name = 'vtiger_tmp_write_group_sharing_per';
                $var_name = $module . '_share_write_permission';
            }
            // Lookup for the variable if not set through function argument
            if (!$varNameArr)
                $varNameArr = $$var_name;
            $grp_arr = [];
            if (sizeof($varNameArr['GROUP']) > 0) {
                foreach ($varNameArr['GROUP'] as $grpid => $grpusers) {
                    if (!in_array($grpid, $grp_arr)) {
                        $query = "insert into " . $table_name . " values(?,?,?)";
                        $adb->pquery($query, array($userId, $tabid, $grpid));
                        $grp_arr[] = $grpid;
                    }
                }
            }
        }
    }

    /**
     * Function to populate the read/write Sharing permissions related module data for the specified user into the database
     * 
     * @param string $enttype Can have the value of User or Group
     * @param int $userId User ID
     * @param string $module Module name
     * @param string $relmodule Related module name
     * @param string $pertype Can have the value of read or write
     * @param array|false $varNameArr Variable to use instead of including the sharing access again
     */
    public static function populateRelatedSharingPrivileges($enttype, $userId, $module, $relmodule, $pertype, $varNameArr = false): void
    {
        $adb = PearDatabase::getInstance();
        $tabid = \App\Utils\ModuleUtils::getModuleId($module);
        $reltabid = \App\Utils\ModuleUtils::getModuleId($relmodule);

        if (!$varNameArr) {
            \vtlib\Deprecated::checkFileAccessForInclusion('user_privileges/sharing_privileges_' . $userId . '.php');
            require('user_privileges/sharing_privileges_' . $userId . '.php');
        }

        if ($enttype == 'USER') {
            if ($pertype == 'read') {
                $table_name = 'vtiger_tmp_read_user_rel_sharing_per';
                $var_name = $module . '_' . $relmodule . '_share_read_permission';
            } elseif ($pertype == 'write') {
                $table_name = 'vtiger_tmp_write_user_rel_sharing_per';
                $var_name = $module . '_' . $relmodule . '_share_write_permission';
            }
            // Lookup for the variable if not set through function argument
            if (!$varNameArr)
                $varNameArr = $$var_name;
            $user_arr = [];
            if (sizeof($varNameArr['ROLE']) > 0) {
                foreach ($varNameArr['ROLE'] as $roleid => $roleusers) {
                    foreach ($roleusers as $user_id) {
                        if (!in_array($user_id, $user_arr)) {
                            $query = "insert into " . $table_name . " values(?,?,?,?)";
                            $adb->pquery($query, array($userId, $tabid, $reltabid, $user_id));
                            $user_arr[] = $user_id;
                        }
                    }
                }
            }
            if (sizeof($varNameArr['GROUP']) > 0) {
                foreach ($varNameArr['GROUP'] as $grpid => $grpusers) {
                    foreach ($grpusers as $user_id) {
                        if (!in_array($user_id, $user_arr)) {
                            $query = "insert into " . $table_name . " values(?,?,?,?)";
                            $adb->pquery($query, array($userId, $tabid, $reltabid, $user_id));
                            $user_arr[] = $user_id;
                        }
                    }
                }
            }
        } elseif ($enttype == 'GROUP') {
            if ($pertype == 'read') {
                $table_name = 'vtiger_tmp_read_group_rel_sharing_per';
                $var_name = $module . '_' . $relmodule . '_share_read_permission';
            } elseif ($pertype == 'write') {
                $table_name = 'vtiger_tmp_write_group_rel_sharing_per';
                $var_name = $module . '_' . $relmodule . '_share_write_permission';
            }
            // Lookup for the variable if not set through function argument
            if (!$varNameArr)
                $varNameArr = $$var_name;
            $grp_arr = [];
            if (sizeof($varNameArr['GROUP']) > 0) {
                foreach ($varNameArr['GROUP'] as $grpid => $grpusers) {
                    if (!in_array($grpid, $grp_arr)) {
                        $query = "insert into " . $table_name . " values(?,?,?,?)";
                        $adb->pquery($query, array($userId, $tabid, $reltabid, $grpid));
                        $grp_arr[] = $grpid;
                    }
                }
            }
        }
    }

    // Helper methods for array construction

    /**
     * Converts the input array to a single string to facilitate the writing of the input array in a flat file
     * 
     * @param array $var Input array
     * @return string Contains the whole array in a single string
     */
    public static function constructArray($var): string
    {
        if (is_array($var)) {
            $code = '[';
            foreach ($var as $key => $value) {
                $code .= "'" . $key . "'=>" . $value . ',';
            }
            $code .= ']';
            return $code;
        }
        return '';
    }

    /**
     * Converts the input array to a single string to facilitate the writing of the input array in a flat file
     * 
     * @param array $var Input array
     * @return string Contains the whole array in a single string
     */
    public static function constructSingleStringValueArray($var): string
    {
        $size = sizeof($var);
        $i = 1;
        if (is_array($var)) {
            $code = '[';
            foreach ($var as $key => $value) {
                if ($i < $size) {
                    $code .= $key . "=>'" . $value . "',";
                } else {
                    $code .= $key . "=>'" . $value . "'";
                }
                $i++;
            }
            $code .= ']';
            return $code;
        }
        return '';
    }

    /**
     * Converts the input array to a single string to facilitate the writing of the input array in a flat file
     * 
     * @param array $var Input array
     * @return string Contains the whole array in a single string
     */
    public static function constructSingleStringKeyAndValueArray($var): string
    {
        $size = sizeof($var);
        $i = 1;
        if (is_array($var)) {
            $code = '[';
            foreach ($var as $key => $value) {
                if ($i < $size) {
                    $code .= "'" . $key . "'=>" . $value . ",";
                } else {
                    $code .= "'" . $key . "'=>" . $value;
                }
                $i++;
            }
            $code .= ']';
            return $code;
        }
        return '';
    }

    /**
     * Converts the input array to a single string to facilitate the writing of the input array in a flat file
     * 
     * @param array $var Input array
     * @return string Contains the whole array in a single string
     */
    public static function constructSingleArray($var): string
    {
        if (is_array($var)) {
            $code = '[';
            foreach ($var as $value) {
                $code .= $value . ',';
            }
            $code .= ']';
            return $code;
        }
        return '';
    }

    /**
     * Converts the input array to a single string to facilitate the writing of the input array in a flat file
     * 
     * @param array $var Input array
     * @return string Contains the whole array in a single string
     */
    public static function constructSingleCharArray($var): string
    {
        if (is_array($var)) {
            $code = '[';
            foreach ($var as $value) {
                $code .= "'" . $value . "',";
            }
            $code .= ']';
            return $code;
        }
        return '';
    }

    /**
     * Converts the input array to a single string to facilitate the writing of the input array in a flat file
     * 
     * @param array $var Input array
     * @return string Contains the whole array in a single string
     */
    public static function constructTwoDimensionalArray($var): string
    {
        if (is_array($var)) {
            $code = '[';
            foreach ($var as $key => $secarr) {
                $code .= $key . '=>[';
                foreach ($secarr as $seckey => $secvalue) {
                    $code .= $seckey . '=>' . $secvalue . ',';
                }
                $code .= '],';
            }
            $code .= ']';
            return $code;
        }
        return '';
    }

    /**
     * Converts the input array to a single string to facilitate the writing of the input array in a flat file
     * 
     * @param array $var Input array
     * @return string Contains the whole array in a single string
     */
    public static function constructTwoDimensionalValueArray($var): string
    {
        if (is_array($var)) {
            $code = '[';
            foreach ($var as $key => $secarr) {
                $code .= $key . '=>array(';
                foreach ($secarr as $seckey => $secvalue) {
                    $code .= $secvalue . ',';
                }
                $code .= '),';
            }
            $code .= ']';
            return $code;
        }
        return '';
    }

    /**
     * Converts the input array to a single string to facilitate the writing of the input array in a flat file
     * 
     * @param array $var Input array
     * @return string Contains the whole array in a single string
     */
    public static function constructTwoDimensionalCharIntSingleArray($var): string
    {
        if (is_array($var)) {
            $code = '[';
            foreach ($var as $key => $secarr) {
                $code .= "'" . $key . "'=>[";
                foreach ($secarr as $seckey => $secvalue) {
                    $code .= $seckey . ',';
                }
                $code .= '],';
            }
            $code .= ']';
            return $code;
        }
        return '';
    }

    /**
     * Converts the input array to a single string to facilitate the writing of the input array in a flat file
     * 
     * @param array $var Input array
     * @return string Contains the whole array in a single string
     */
    public static function constructTwoDimensionalCharIntSingleValueArray($var): string
    {
        if (is_array($var)) {
            $code = '[';
            foreach ($var as $key => $secarr) {
                $code .= "'" . $key . "'=>[";
                foreach ($secarr as $seckey => $secvalue) {
                    $code .= $secvalue . ',';
                }
                $code .= '],';
            }
            $code .= ']';
            return $code;
        }
        return '';
    }

    /**
     * Function to recalculate the Sharing Rules for all the vtiger_users
     * This function will recalculate all the sharing rules for all the vtiger_users in the Organization and will write them in flat files
     */
    public static function RecalculateSharingRules()
    {
        \App\Log\Log::trace("Entering RecalculateSharingRules() method ...");
        $adb = \App\Database\PearDatabase::getInstance();

        $query = "select id from vtiger_users where deleted=0";
        $result = $adb->pquery($query, []);
        $num_rows = $adb->num_rows($result);
        for ($i = 0; $i < $num_rows; $i++) {
            $id = $adb->query_result($result, $i, 'id');
            self::createUserPrivilegesFile($id);
            self::createUserSharingPrivilegesFile($id);
        }
        \App\Log\Log::trace("Exiting RecalculateSharingRules method ...");
    }
}
