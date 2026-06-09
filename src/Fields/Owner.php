<?php
namespace App\Fields;

/**
 * Owner class
 * @package YetiForce.App
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 */
class Owner
{

	protected string|false $moduleName = false;
	protected ?string $searchValue = null;
	protected \App\Modules\Users\Models\Record $currentUser;

	/**
	 * Function to get the instance
	 * @param string|false $moduleName
	 * @param \App\Modules\Users\Models\Record|null $currentUser
	 * @param \App\Http\Vtiger_Request|null $request
	 * @return self
	 */
	public static function getInstance(
		string|false $moduleName = false,
		?\App\Modules\Users\Models\Record $currentUser = null,
		?\App\Http\Vtiger_Request $request = null,
	): self {
		if ($currentUser === null) {
			$currentUser = \App\User\CurrentUser::get();
		}

		$cacheKey = $moduleName . $currentUser->getId();
		$instance = \App\Cache\Cache::get('App\Fields\Owner', $cacheKey);
		if ($instance === false) {
			$instance = new self();
			$instance->moduleName = $moduleName != false ? $moduleName : ($request !== null ? $request->get('module') : false);
			$instance->currentUser = $currentUser;
			\App\Cache\Cache::save('App\Fields\Owner', $cacheKey, $instance);
		}
		return $instance;
	}

	public function find($value)
	{
		$this->searchValue = $value;
	}

	/**
	 * Function to get all the accessible groups
	 * @return array
	 */
	public function getAccessibleGroups($private = '', $fieldType = false, $translate = false)
	{
		$cacheKey = $private . $this->moduleName . $fieldType;
		$accessibleGroups = \App\Cache\Cache::get('getAccessibleGroups', $cacheKey);
		if ($accessibleGroups === false) {
			$currentUserRoleModel = \App\Modules\Settings\Roles\Models\Record::getInstanceById((int) $this->currentUser->getRole());
			if (!empty($fieldType) && $currentUserRoleModel->get('allowassignedrecordsto') == '5' && $private != 'Public') {
				$accessibleGroups = $this->getAllocation('groups', $private, $fieldType);
			} else {
				$accessibleGroups = $this->getGroups(false, $private);
			}
			\App\Cache\Cache::save('getAccessibleGroups', $cacheKey, $accessibleGroups);
		}
		if ($translate) {
			foreach ($accessibleGroups as &$name) {
				$name = \App\Runtime\Vtiger_Language_Handler::translate($name);
			}
		}
		if (!empty($this->searchValue)) {
			$searchValue = strtolower($this->searchValue);
			$accessibleGroups = array_filter($accessibleGroups, function ($name) use ($searchValue) {
				return strstr(strtolower($name), $searchValue);
			});
		}
		return $accessibleGroups;
	}

	/**
	 * Function to get all the accessible users
	 * @param string $private
	 * @param mixed $fieldType
	 * @return array
	 */
	public function getAccessibleUsers($private = '', $fieldType = false)
	{
		$cacheKey = $private . $this->moduleName . $fieldType . $fieldType;
		$accessibleUser = \App\Cache\Cache::get('getAccessibleUsers', $cacheKey);
		if ($accessibleUser === false) {
			$currentUserRoleModel = \App\Modules\Settings\Roles\Models\Record::getInstanceById((int) $this->currentUser->getRole());
			if ($currentUserRoleModel->get('allowassignedrecordsto') == '1' || $private == 'Public') {
				$accessibleUser = $this->getUsers(false, 'Active', '', $private, true);
			} else if ($currentUserRoleModel->get('allowassignedrecordsto') == '2') {
				$currentUserRoleModel = \App\Modules\Settings\Roles\Models\Record::getInstanceById((int) $this->currentUser->getRole());
				$sameLevelRoles = array_keys($currentUserRoleModel->getSameLevelRoles());
				$childernRoles = \App\Security\PrivilegeUtil::getRoleSubordinates((int) $this->currentUser->getRole());
				$roles = array_merge($sameLevelRoles, $sameLevelRoles);
				$accessibleUser = $this->getUsers(false, 'Active', '', '', false, array_unique($roles));
			} else if ($currentUserRoleModel->get('allowassignedrecordsto') == '3') {
				$childernRoles = \App\Security\PrivilegeUtil::getRoleSubordinates((int) $this->currentUser->getRole());
				$accessibleUser = $this->getUsers(false, 'Active', '', '', false, array_unique($childernRoles));
				$accessibleUser[$this->currentUser->getId()] = $this->currentUser->getName();
			} else if (!empty($fieldType) && $currentUserRoleModel->get('allowassignedrecordsto') == '5') {
				$accessibleUser = $this->getAllocation('users', '', $fieldType);
			} else {
				$accessibleUser = [
					$this->currentUser->getId() => $this->currentUser->getName(),
				];
			}
			\App\Cache\Cache::save('getAccessibleUsers', $cacheKey, $accessibleUser);
		}
		return $accessibleUser;
	}

	public function getAccessible($private = '', $fieldType = false, $translate = false)
	{
		return [
			'users' => $this->getAccessibleUsers($private, $fieldType),
			'groups' => $this->getAccessibleGroups($private, $fieldType, $translate)
		];
	}

	public function getAllocation($mode, $private = '', $fieldType = false, $request = null)
	{
		$moduleName = $this->moduleName;
		if ($request !== null && $request->get('parent') == 'Settings') {
			$moduleName = $request->get('module') ?: $moduleName;
		}

		$result = [];
		$usersGroups = \App\Modules\Settings\RecordAllocation\Models\Module::getRecordAllocationByModule($fieldType, $moduleName);
		$usersGroups = ($usersGroups && $usersGroups[$this->currentUser->getId()]) ? $usersGroups[$this->currentUser->getId()] : [];
		if ($mode == 'users') {
			$users = $usersGroups ? $usersGroups['users'] : [];
			if (!empty($users)) {
				$result = $this->getUsers(false, 'Active', $users);
			}
		} else {
			$groups = $usersGroups ? $usersGroups['groups'] : [];
			if (!empty($groups)) {
				$groupsAll = $this->getGroups(false, $private);
				foreach ($groupsAll as $ID => $name) {
					if (in_array($ID, $groups)) {
						$result[$ID] = $name;
					}
				}
			}
		}
		return $result;
	}

	/**
	 * Function initiates users list
	 * @param string $status
	 * @param mixed $assignedUser
	 * @param string $private
	 * @param mixed $roles
	 * @return array
	 */
	public function &initUsers($status = 'Active', $assignedUser = '', $private = '', $roles = false)
	{
		$cacheKeyMod = $private === 'private' ? $this->moduleName : '';
		$cacheKeyAss = is_array($assignedUser) ? md5(json_encode($assignedUser)) : $assignedUser;
		$cacheKeyRole = is_array($roles) ? md5(json_encode($roles)) : $roles;
		$cacheKey = $cacheKeyMod . $status . $cacheKeyAss . $private . $cacheKeyRole;
		if (!\App\Cache\Cache::has('getUsers', $cacheKey)) {
			$entityData = \App\Utils\ModuleUtils::getEntityInfo('Users');
			$query = $this->getQueryInitUsers($private, $status, $roles);
			if (!empty($assignedUser)) {
				$query->where(['vtiger_users.id' => $assignedUser]);
			}
			$tempResult = [];
			/** @var \yii\db\DataReader $dataReader */
			$dataReader = $query->createCommand()->query();
			// Get the id and the name.
			while ($row = $dataReader->read()) {
				$fullName = '';
				foreach ($entityData['fieldnameArr'] as &$field) {
					$fullName .= ' ' . $row[$field];
				}
				$row['fullName'] = trim($fullName);
				$tempResult[$row['id']] = $row;
			}
			\App\Cache\Cache::save('getUsers', $cacheKey, $tempResult);
		}
		$tmp = \App\Cache\Cache::get('getUsers', $cacheKey);
		return $tmp;
	}

	/**
	 * Function gets sql query
	 * @param mixed $private
	 * @param mixed $status
	 * @param mixed $roles
	 * @return \App\Db\Query
	 */
	public function getQueryInitUsers($private = false, $status = false, $roles = false)
	{
		$entityData = \App\Utils\ModuleUtils::getEntityInfo('Users');
		$selectFields = array_unique(array_merge($entityData['fieldnameArr'], ['id' => 'id', 'is_admin', 'cal_color', 'status']));
		// Including deleted vtiger_users for now.
		if ($private === 'private') {
			$userPrivileges = \App\Modules\Users\Models\Privileges::getPrivilegesFile($this->currentUser->getId());
			if ($userPrivileges === null) {
				\App\Log\Log::error("User privileges file not found for user: " . $this->currentUser->getId());
				return (new \App\Db\Query())->from('vtiger_users')->where('0=1');
			}
			\App\Log\Log::trace('Sharing is Private. Only the current user should be listed');
			$query = new \App\Db\Query ();
			$query->select($selectFields)->from('vtiger_users')->where(['id' => $this->currentUser->getId()]);
			$queryByUserRole = new \App\Db\Query ();
			$selectFields['id'] = 'vtiger_user2role.userid';
			$queryByUserRole->
				select($selectFields)
				->from('vtiger_user2role')
				->innerJoin('vtiger_users', 'vtiger_user2role.userid = vtiger_users.id')
				->innerJoin('vtiger_role', 'vtiger_user2role.roleid = vtiger_role.roleid')
				->where(['vtiger_role.parentrole' => $userPrivileges['parent_role_seq'] . '::%']);
			$queryBySharing = new \App\Db\Query ();
			$selectFields['id'] = 'shareduserid';
			$queryBySharing->
				select($selectFields)
				->from('vtiger_tmp_write_user_sharing_per')
				->innerJoin('vtiger_users', 'vtiger_tmp_write_user_sharing_per.shareduserid = vtiger_users.id')
				->where(['vtiger_tmp_write_user_sharing_per.userid' => $this->currentUser->getId(), 'vtiger_tmp_write_user_sharing_per.tabid' => \App\Utils\ModuleUtils::getModuleId($this->moduleName)]);
			$query->union($queryByUserRole)->union($queryBySharing);
		} elseif ($roles !== false) {
			$query = (new \App\Db\Query())->select($selectFields)->from('vtiger_users')->innerJoin('vtiger_user2role', 'vtiger_users.id = vtiger_user2role.userid')->where(['vtiger_user2role.roleid' => $roles]);
		} else {
			\App\Log\Log::trace('Sharing is Public. All vtiger_users should be listed');
			$query = new \App\Db\Query();
			$query->select($selectFields)->from('vtiger_users');
		}
		$where = []; // PHP 8.2+: Initialize as array instead of false to avoid deprecation warning
		if (!empty($this->searchValue)) {
			$where []= ['like', \App\Utils\ModuleUtils::getSqlForNameInDisplayFormat('Users'), $this->searchValue];
		}
		if ($status) {
			$where []= ['status' => $status];
		}
		if ($where) {
			$query->where(array_merge(['and'], $where));
		}
		return $query;
	}

	/** Function returns the user key in user array
	 * @param $addBlank -- boolean:: Type boolean
	 * @param $status -- user status:: Type string
	 * @param $assignedUser -- user id:: Type string or array
	 * @param $private -- sharing type:: Type string
	 * @param $onlyAdmin -- show only admin users:: Type boolean
	 * @returns $users -- user array:: Type array
	 *
	 */
	public function getUsers($addBlank = false, $status = 'Active', $assignedUser = '', $private = '', $onlyAdmin = false, $roles = false)
	{
		\App\Log\Log::trace("Entering getUsers($addBlank,$status,$assignedUser,$private) method ...");

		$tempResult = $this->initUsers($status, $assignedUser, $private);
		$users = [];
		if ($addBlank === true) {
			// Add in a blank row
			$users[''] = '';
		}
		$adminInList = \App\Core\AppConfig::performance('SHOW_ADMINISTRATORS_IN_USERS_LIST');
		$isAdmin = $this->currentUser->isAdmin();
		foreach ($tempResult as $key => $row) {
			if (!$onlyAdmin || $isAdmin || !(!$adminInList && $row['is_admin'] == 'on')) {
				$users[$key] = $row['fullName'];
			}
		}
		asort($users);
		\App\Log\Log::trace('Exiting getUsers method ...');
		return $users;
	}

	public function getGroups($addBlank = true, $private = '', $request = null)
	{
		\App\Log\Log::trace("Entering getGroups($addBlank,$private) method ...");
		$moduleName = '';
		$tabid = 0;
		if (($request === null || $request->get('parent') != 'Settings') && $this->moduleName) {
			$moduleName = $this->moduleName;
			$tabid = \App\Utils\ModuleUtils::getModuleId($moduleName);
		}
		$cacheKey = $addBlank . $private . $moduleName;
		if (\App\Cache\Cache::has('OwnerGroups', $cacheKey)) {
			return \App\Cache\Cache::get('OwnerGroups', $cacheKey);
		}
		$db = \App\Database\PearDatabase::getInstance();
		// Including deleted vtiger_users for now.
		\App\Log\Log::trace('Sharing is Public. All vtiger_users should be listed');
		$query = 'SELECT groupid, groupname FROM vtiger_groups';
		$tempResult = $params = [];

		if (!empty($moduleName) && $moduleName != 'CustomView') {
			$query .= ' WHERE groupid IN (SELECT groupid FROM vtiger_group2modules WHERE tabid = ?)';
			$params[] = $tabid;
		}
		if ($private == 'private') {
			$userPrivileges = \App\Modules\Users\Models\Privileges::getPrivilegesFile($this->currentUser->getId());
			if ($userPrivileges === null) {
				\App\Log\Log::error("User privileges file not found for user: " . $this->currentUser->getId());
				return [];
			}
			if (strpos($query, 'WHERE') === false) {
				$query .= ' WHERE';
			} else {
				$query .= ' AND';
			}
			$query .= ' groupid=?';
			array_push($params, $this->currentUser->getId());

			if (count($userPrivileges['groups']) != 0) {
				$query .= ' || vtiger_groups.groupid in (' . \App\Utils\Utils::generateQuestionMarks($userPrivileges['groups']) . ')';
				array_push($params, $userPrivileges['groups']);
			}
			\App\Log\Log::trace('Sharing is Private. Only the current user should be listed');
			$query .= ' union select vtiger_group2role.groupid as groupid,vtiger_groups.groupname as groupname from vtiger_group2role inner join vtiger_groups on vtiger_groups.groupid=vtiger_group2role.groupid inner join vtiger_role on vtiger_role.roleid=vtiger_group2role.roleid where vtiger_role.parentrole like ?';
			array_push($params, $userPrivileges['parent_role_seq'] . '::%');

			if (count($userPrivileges['groups']) != 0) {
				$query .= ' union select vtiger_groups.groupid as groupid,vtiger_groups.groupname as groupname from vtiger_groups inner join vtiger_group2rs on vtiger_groups.groupid=vtiger_group2rs.groupid where vtiger_group2rs.roleandsubid in (' . \App\Utils\Utils::generateQuestionMarks($userPrivileges['parent_roles']) . ')';
				array_push($params, $userPrivileges['parent_roles']);
			}

			$query .= ' union select sharedgroupid as groupid,vtiger_groups.groupname as groupname from vtiger_tmp_write_group_sharing_per inner join vtiger_groups on vtiger_groups.groupid=vtiger_tmp_write_group_sharing_per.sharedgroupid where vtiger_tmp_write_group_sharing_per.userid=?';
			array_push($params, $this->currentUser->getId());

			$query .= ' and vtiger_tmp_write_group_sharing_per.tabid=?';
			array_push($params, $tabid);
		}
		$query .= ' order by groupname ASC';
		$result = $db->pquery($query, $params);

		if ($addBlank === true) {
			// Add in a blank row
			$tempResult[''] = '';
		}

		// Get the id and the name.
		while ($row = $db->getRow($result)) {
			$tempResult[$row['groupid']] = \App\Utils\ListViewUtils::decodeHtml($row['groupname']);
		}
		\App\Cache\Cache::save('OwnerGroups', $cacheKey, $tempResult);
		\App\Log\Log::trace('Exiting getGroups method ...');
		return $tempResult;
	}

	/**
	 * Function returns List of Accessible Users for a Module
	 * @return array
	 */
	public function getAccessibleGroupForModule()
	{
		$curentUserPrivileges = \App\Modules\Users\Models\Privileges::getCurrentUserPrivilegesModel();
		if ($this->currentUser->isAdmin() || $curentUserPrivileges->hasGlobalWritePermission()) {
			$groups = $this->getAccessibleGroups('');
		} else {
			$sharingAccessModel = \App\Modules\Settings\SharingAccess\Models\Module::getInstance($this->moduleName);
			if ($sharingAccessModel instanceof \App\Modules\Settings\SharingAccess\Models\Module && $sharingAccessModel->isPrivate()) {
				$groups = $this->getAccessibleGroups('private');
			} else {
				$groups = $this->getAccessibleGroups('');
			}
		}
		return $groups;
	}

	/**
	 * Function returns List of Accessible Users for a Module
	 * @return array
	 */
	public function getAccessibleUsersForModule()
	{
		$curentUserPrivileges = \App\Modules\Users\Models\Privileges::getCurrentUserPrivilegesModel();
		if ($this->currentUser->isAdmin() || $curentUserPrivileges->hasGlobalWritePermission()) {
			$users = $this->getAccessibleUsers('');
		} else {
			$sharingAccessModel = \App\Modules\Settings\SharingAccess\Models\Module::getInstance($this->moduleName);
			if ($sharingAccessModel instanceof \App\Modules\Settings\SharingAccess\Models\Module && $sharingAccessModel->isPrivate()) {
				$users = $this->getAccessibleUsers('private');
			} else {
				$users = $this->getAccessibleUsers('');
			}
		}
		return $users;
	}

	public function getUsersAndGroupForModuleList(int|false $view = false, array|false $conditions = false)
	{
		$queryGenerator = new \App\QueryField\QueryGenerator($this->moduleName, $this->currentUser->getId());
		if ($view) {
			$queryGenerator->initForCustomViewById($view);
		}
		if ($conditions) {
			$queryGenerator->addNativeCondition($conditions['condition']);
			if (!empty($conditions['join'])) {
				foreach ($conditions['join'] as $join) {
					$queryGenerator->addJoin($join);
				}
			}
		}
		$queryGenerator->setFields(['assigned_user_id']);
		$ids = $queryGenerator->createQuery()->distinct()->createCommand()->queryColumn();
		$users = $groups = [];
		$adminInList = \App\Core\AppConfig::performance('SHOW_ADMINISTRATORS_IN_USERS_LIST');
		foreach ($ids as $id) {
			/** @var \App\Modules\Users\Models\Record $userModel */
			$userModel = \App\Modules\Users\Models\Record::getInstanceById($id, 'Users');
			$name = $userModel->getName();
			if (!empty($name) && ($adminInList || !$userModel->isAdmin())) {
				$users[$id] = $name;
			}
		}
		$diffIds = array_diff($ids, array_keys($users));
		if ($diffIds) {
			foreach (array_values($diffIds) as $id) {
				$name = self::getGroupName($id);
				if (!empty($name)) {
					$groups[$id] = $name;
				}
			}
		}
		return ['users' => $users, 'group' => $groups];
	}

	public static function getAllUsers($status = 'Active')
	{
		$instance = new self();
		return $instance->initUsers($status);
	}

	protected static $usersIdsCache = [];

	public static function getUsersIds($status = 'Active')
	{
		if (!isset(self::$usersIdsCache[$status])) {
			$rows = [];
			if (\App\Core\AppConfig::performance('ENABLE_CACHING_USERS')) {
				$rows = \App\Security\PrivilegeFile::getUser('id');
			} else {
				$instance = new self();
				$rows = $instance->initUsers($status);
			}
			self::$usersIdsCache[$status] = array_keys($rows);
		}
		return self::$usersIdsCache[$status];
	}

	protected static $ownerLabelCache = [];
	protected static $userLabelCache = [];
	protected static $groupLabelCache = [];
	protected static $groupIdCache = [];

	public static function getLabel($mixedId)
	{
		$multiMode = is_array($mixedId);
		$ids = $multiMode ? $mixedId : [$mixedId];
		$missing = [];
		foreach ($ids as $id) {
			if ($id && !isset(self::$ownerLabelCache[$id])) {
				$missing[] = $id;
			}
		}
		if (!empty($missing)) {
			foreach ($missing as $userId) {
				self::getUserLabel($userId);
			}
			$diffIds = array_diff($missing, array_keys(self::$ownerLabelCache));
			if ($diffIds) {
				foreach ($diffIds as $groupId) {
					self::getGroupName($groupId);
				}
			}
		}
		$result = [];
		foreach ($ids as $id) {
			if (isset(self::$ownerLabelCache[$id])) {
				$result[$id] = self::$ownerLabelCache[$id];
			} else {
				$result[$id] = NULL;
			}
		}
		return $multiMode ? $result : array_shift($result);
	}

	public static function getGroupName($id)
	{
		if (isset(self::$groupLabelCache[$id])) {
			return self::$groupLabelCache[$id];
		}
		$label = false;
		$instance = new self();
		$groups = $instance->getGroups(false);
		if (isset($groups[$id])) {
			$label = $groups[$id];
			self::$groupLabelCache[$id] = self::$ownerLabelCache[$id] = $label;
			self::$groupIdCache[$label] = $id;
		}
		return $label;
	}

	/**
	 * Function to get the Group Id for a given group groupname
	 * @param string $name
	 * @return int
	 */
	public static function getGroupId($name)
	{
		if (isset(self::$groupIdCache[$name])) {
			return self::$groupIdCache[$name];
		}
		$id = false;
		$instance = new self();
		$groups = array_flip($instance->getGroups(false));
		if (isset($groups[$name])) {
			$id = self::$groupIdCache[$name] = $groups[$name];
		}
		return $id;
	}

	public static function getUserLabel($id, $single = false)
	{
		if (isset(self::$userLabelCache[$id])) {
			return self::$userLabelCache[$id];
		}

		if (\App\Core\AppConfig::performance('ENABLE_CACHING_USERS')) {
			$users = \App\Security\PrivilegeFile::getUser('id');
		} else {
			$instance = new self();
			if ($single) {
				$users = $instance->initUsers('', $id);
			} else {
				$users = $instance->initUsers('');
			}
		}
		foreach ($users as $uid => &$user) {
			self::$userLabelCache[$uid] = $user['fullName'];
			self::$ownerLabelCache[$uid] = $user['fullName'];
		}
		return isset($users[$id]) ? $users[$id]['fullName'] : false;
	}

	protected static $typeCache = [];

	/**
	 * Function checks record type
	 * @param int $id
	 * @return string
	 */
	public static function getType($id)
	{
		if (isset(self::$typeCache[$id])) {
			return self::$typeCache[$id];
		}
		if (\App\Core\AppConfig::performance('ENABLE_CACHING_USERS')) {
			$users = \App\Security\PrivilegeFile::getUser('id');
			$isExists = isset($users[$id]);
		} else {
			$isExists = (new \App\Db\Query())
				->from('vtiger_users')
				->where(['id' => $id])
				->exists();
		}
		$result = $isExists ? 'Users' : 'Groups';
		self::$typeCache[$id] = $result;
		return $result;
	}
}
