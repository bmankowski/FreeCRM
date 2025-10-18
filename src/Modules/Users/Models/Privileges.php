<?php

namespace App\Modules\Users\Models;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * *********************************************************************************** */

/**
 * User Privileges Model Class
 */

class Privileges extends \App\Modules\Vtiger\Models\Model
{

	/**
	 * Function to get the Display Name for the record
	 * @return string - Entity Display Name for the record
	 */
	public function getName()
	{
		$entityData = \App\Module::getEntityInfo('Users');
		$colums = [];
		foreach ($entityData['fieldnameArr'] as $fieldname) {
			$colums[] = $this->get($fieldname);
		}
		return implode(' ', $colums);
	}

	/**
	 * Function to get the Global Read Permission for the user
	 * @return <Number> 0/1
	 */
	protected function getGlobalReadPermission()
	{
		$profileGlobalPermissions = $this->get('profile_global_permission');
		return $profileGlobalPermissions[\App\Modules\Settings\Profiles\Models\Module::GLOBAL_ACTION_VIEW];
	}

	/**
	 * Function to get the Global Write Permission for the user
	 * @return <Number> 0/1
	 */
	protected function getGlobalWritePermission()
	{
		$profileGlobalPermissions = $this->get('profile_global_permission');
		return $profileGlobalPermissions[\App\Modules\Settings\Profiles\Models\Module::GLOBAL_ACTION_EDIT];
	}

	/**
	 * Function to check if the user has Global Read Permission
	 * @return boolean true/false
	 */
	public function hasGlobalReadPermission()
	{
		return ($this->get("is_admin") == "on" ||
			$this->getGlobalReadPermission() === \App\Modules\Settings\Profiles\Models\Module::IS_PERMITTED_VALUE ||
			$this->getGlobalWritePermission() === \App\Modules\Settings\Profiles\Models\Module::IS_PERMITTED_VALUE);
	}

	/**
	 * Function to check if the user has Global Write Permission
	 * @return boolean true/false
	 */
	public function hasGlobalWritePermission()
	{
		return ($this->get("is_admin") == "on" || $this->getGlobalWritePermission() === \App\Modules\Settings\Profiles\Models\Module::IS_PERMITTED_VALUE);
	}

	public function hasGlobalPermission($actionId)
	{
		if ($actionId == \App\Modules\Settings\Profiles\Models\Module::GLOBAL_ACTION_VIEW) {
			return $this->hasGlobalReadPermission();
		}
		if ($actionId == \App\Modules\Settings\Profiles\Models\Module::GLOBAL_ACTION_EDIT) {
			return $this->hasGlobalWritePermission();
		}
		return false;
	}

	/**
	 * Function to check whether the user has access to a given module by tabid
	 * @param int $mixed
	 * @return boolean true/false
	 */
	public function hasModulePermission($mixed)
	{
		$profileTabsPermissions = $this->get('profile_tabs_permission');
		$moduleModel = \App\Modules\Vtiger\Models\Module::getInstance($mixed);
		return !empty($moduleModel) && $moduleModel->isActive() && (($this->get('is_admin') == 'on' || $profileTabsPermissions[$moduleModel->getId()] === 0));
	}

	/**
	 * Function to check whether the user has access to the specified action/operation on a given module by tabid
	 * @param <Number> $tabId
	 * @param <String/Number> $action
	 * @return boolean true/false
	 */
	public function hasModuleActionPermission($mixed, $action)
	{
		if (!is_object($action) || !($action instanceof \App\Modules\Vtiger\Models\Action)) {
			$action = \App\Modules\Vtiger\Models\Action::getInstance($action);
		}
		$actionId = $action->getId();
		$profileTabsPermissions = $this->get('profile_action_permission');
		$moduleModel = \App\Modules\Vtiger\Models\Module::getInstance($mixed);
		return $moduleModel->isActive() && (($this->get("is_admin") == "on" || $profileTabsPermissions[$moduleModel->getId()][$actionId] === \App\Modules\Settings\Profiles\Models\Module::IS_PERMITTED_VALUE));
	}

	/**
	 * Static Function to get the instance of the User Privileges model from the given list of key-value array
	 * @param <Array> $valueMap
	 * @return Users_Privilege_Model object
	 */
	public static function getInstance($valueMap)
	{
		$instance = new self();
		$instance->setData($valueMap);
		return $instance;
	}

	protected static $userPrivilegesModelCache = [];

	/**
	 * Static Function to get the instance of the User Privileges model, given the User id
	 * @param <Number> $userId
	 * @return Users_Privilege_Model object
	 */
	public static function getInstanceById($userId, $module = null)
	{
		if (empty($userId))
			return null;

		if (isset(self::$userPrivilegesModelCache[$userId])) {
			return self::$userPrivilegesModelCache[$userId];
		}
		$valueMap = \App\User::getPrivilegesFile($userId);
		if (is_array($valueMap['user_info'])) {
			$valueMap = array_merge($valueMap, $valueMap['user_info']);
		}
		$instance = self::getInstance($valueMap);
		self::$userPrivilegesModelCache[$userId] = $instance;
		return $instance;
	}

	/**
	 * Static function to get the User Privileges Model for the current user
	 * @return Users_Privilege_Model object
	 */
	public static function getCurrentUserPrivilegesModel()
	{
		return self::getInstanceById(\App\User::getCurrentUserId());
	}

	/**
	 * Function to check permission for a Module/Action/Record
	 * @param string $moduleName
	 * @param string $actionName
	 * @param <Number> $record
	 * @return Boolean
	 */
	public static function isPermitted($moduleName, $actionName = null, $record = false)
	{
		return \App\Privilege::isPermitted($moduleName, $actionName, $record);
	}

	public static function getLastPermittedAccessLog()
	{
		return vglobal('isPermittedLog');
	}

	/**
	 * Function returns non admin access control check query
	 * @param string $module
	 * @return string
	 */
	public static function getNonAdminAccessControlQuery($module)
	{
		$currentUser = vglobal('current_user');
		return \App\Utils\UserInfoUtil::getNonAdminAccessControlQuery($module, $currentUser);
	}

	protected static $lockEditCache = [];

	public static function checkLockEdit($moduleName, \App\Modules\Vtiger\Models\Record $recordModel)
	{
		$recordId = $recordModel->getId();
		if (isset(self::$lockEditCache[$moduleName . $recordId])) {
			return self::$lockEditCache[$moduleName . $recordId];
		}
		$return = false;
		if (empty($recordId)) {
			self::$lockEditCache[$moduleName . $recordId] = $return;
			return $return;
		}
		require_once ROOT_DIRECTORY . '/src/Modules/com_vtiger_workflow/include.php';
		require_once ROOT_DIRECTORY . '/src/Modules/com_vtiger_workflow/VTEntityMethodManager.php';
		require_once ROOT_DIRECTORY . '/src/Webservices/Retrieve.php';
		$workflows = (new \App\Modules\com_vtiger_workflow\VTWorkflowManager(\App\database\PearDatabase::getInstance()))->getWorkflowsForModule($moduleName, \App\Modules\com_vtiger_workflow\VTWorkflowManager::$BLOCK_EDIT);
		if (count($workflows)) {
			foreach ($workflows as &$workflow) {
				if ($workflow->evaluate($recordModel)) {
					$return = true;
				}
			}
		}
		self::$lockEditCache[$moduleName . $recordId] = $return;
		return $return;
	}

	public static function clearLockEditCache($cacheName = false)
	{
		if ($cacheName) {
			unset(self::$lockEditCache[$cacheName]);
		} else {
			self::$lockEditCache = [];
		}
	}

	/**
	 * Clear user cache
	 * @param int|boolean $userId
	 */
	public static function clearCache($userId = false)
	{
		self::$lockEditCache = [];
		if ($userId) {
			unset(self::$userPrivilegesModelCache[$userId]);
		} else {
			self::$userPrivilegesModelCache = [];
		}
	}

	/**
	 * Function to set Shared Owner
	 * @param int|array|string $userIds
	 * @param int $record
	 */
	public static function setSharedOwner($userIds, $record)
	{
		$saveFull = true;

		$db = \App\Db::getInstance();
		if (\App\Http\AppRequest::get('action') == 'SaveAjax' && \App\Http\AppRequest::has('field') && \App\Http\AppRequest::get('field') != 'shownerid') {
			$saveFull = false;
		}
		if ($saveFull) {
			$db->createCommand()->delete('u_#__crmentity_showners', ['crmid' => $record])->execute();
			if (empty($userIds)) {
				return false;
			}
			if (!is_array($userIds) && $userIds) {
				$userIds = explode(',', $userIds);
			}
			foreach ($userIds as $userId) {
				$db->createCommand()->insert('u_#__crmentity_showners', [
					'crmid' => $record,
					'userid' => $userId,
				])->execute();
			}
		}
	}

	public static function isPermittedByUserId($userId, $moduleName, $actionName = '', $record = false)
	{
		return \App\Privilege::isPermitted($moduleName, $actionName, $record, $userId);
	}

	/**
	 * Function to get set Shared Owner Recursively
	 */
	public static function getSharedRecordsRecursively($recordId, $moduleName)
	{
		\App\Log::trace('Entering Into getSharedRecordsRecursively( ' . $recordId . ', ' . $moduleName . ')');

		$db = \App\database\PearDatabase::getInstance();
		$modulesSchema = [];
		$modulesSchema[$moduleName] = [];
		$modulesSchema['Accounts'] = [
			'Contacts' => ['key' => 'contactid', 'table' => 'vtiger_contactdetails', 'relfield' => 'parentid'],
			'Campaigns' => ['key' => 'campaignid', 'table' => 'vtiger_campaign_records', 'relfield' => 'crmid'],
			'Project' => ['key' => 'projectid', 'table' => 'vtiger_project', 'relfield' => 'linktoaccountscontacts'],
			'HelpDesk' => ['key' => 'ticketid', 'table' => 'vtiger_troubletickets', 'relfield' => 'parent_id']
		];
		$modulesSchema['Project'] = [
			'ProjectMilestone' => ['key' => 'projectmilestoneid', 'table' => 'vtiger_projectmilestone', 'relfield' => 'projectid'],
			'ProjectTask' => ['key' => 'projecttaskid', 'table' => 'vtiger_projecttask', 'relfield' => 'projectid']
		];
		$modulesSchema['HelpDesk'] = [
			'OSSTimeControl' => ['key' => 'osstimecontrolid', 'table' => 'vtiger_osstimecontrol', 'relfield' => 'link']
		];
		$sql = '';
		$params = [];
		$array = [];
		foreach ($modulesSchema[$moduleName] as $key => $module) {
			$sql .= " UNION SELECT " . $module['key'] . " AS id , '" . $key . "' AS module FROM " . $module['table'] . " WHERE " . $module['relfield'] . " = ?";
			$params[] = $recordId;
		}
		if ($sql != '' && $params) {
			$result = $db->pquery(substr($sql, 6), $params);
			while ($row = $db->getRow($result)) {
				$array = array_merge($array, self::getSharedRecordsRecursively($row['id'], $row['module']));
				$array[$row['module']][] = $row['id'];
			}
		}
		\App\Log::trace('Exiting getSharedRecordsRecursively()');
		return $array;
	}

	/**
	 * Get parent record id
	 * @param int $record
	 * @param string|bool $moduleName
	 * @param int $type
	 * @param type $actionid
	 * @return int|bool
	 */
	public static function getParentRecord($record, $moduleName = false, $type = 1, $actionid = false)
	{
		$cacheKey = "$record,$moduleName,$type,$actionid";
		if (\App\Cache::staticHas('PrivilegesParentRecord', $cacheKey)) {
			return \App\Cache::staticGet('PrivilegesParentRecord', $cacheKey);
		}
		$userModel = \App\User::getCurrentUserModel();
		$currentUserId = $userModel->getId();
		$currentUserGroups = $userModel->get('groups');
		settype($currentUserGroups, 'array');
		if (!$moduleName) {
			$recordMetaData = \vtlib\Functions::getCRMRecordMetadata($record);
			$moduleName = $recordMetaData['setype'];
		}
		if ($moduleName == 'Events') {
			$moduleName = 'Calendar';
		}

		$parentRecord = false;
		if ($parentModule = \App\ModuleHierarchy::getModulesMap1M($moduleName)) {
			$parentModuleModel = \App\Modules\Vtiger\Models\Module::getInstance($moduleName);
			$parentModelFields = $parentModuleModel->getFields();

			foreach ($parentModelFields as $fieldName => $fieldModel) {
				if ($fieldModel->isReferenceField() && count(array_intersect($parentModule, $fieldModel->getReferenceList())) > 0) {
					$recordModel = \App\Modules\Vtiger\Models\Record::getInstanceById($record);
					$value = $recordModel->get($fieldName);
					if ($value != '' && $value != 0) {
						$parentRecord = $value;
						continue;
					}
				}
			}
			if ($parentRecord && $type == 2) {
				$rparentRecord = self::getParentRecord($parentRecord, false, $type, $actionid);
				if ($rparentRecord) {
					$parentRecord = $rparentRecord;
				}
			}
			$parentRecord = $record != $parentRecord ? $parentRecord : false;
		} else if (in_array($moduleName, \App\ModuleHierarchy::getModulesMapMMBase())) {
			$db = \App\database\PearDatabase::getInstance();
			$role = $userModel->getRoleInstance();
			$result = $db->pquery('SELECT * FROM vtiger_crmentityrel WHERE crmid=? || relcrmid =?', [$record, $record]);
			while ($row = $db->getRow($result)) {
				$id = $row['crmid'] == $record ? $row['relcrmid'] : $row['crmid'];
				$recordMetaData = \vtlib\Functions::getCRMRecordMetadata($id);
				$permissionsRoleForRelatedField = $role->get('permissionsrelatedfield');
				$permissionsRelatedField = $permissionsRoleForRelatedField == '' ? [] : explode(',', $role->get('permissionsrelatedfield'));
				$relatedPermission = false;
				foreach ($permissionsRelatedField as &$row) {
					if (!$relatedPermission) {
						switch ($row) {
							case 0:
								$relatedPermission = $recordMetaData['smownerid'] == $currentUserId || in_array($recordMetaData['smownerid'], $currentUserGroups);
								break;
							case 1:
								$relatedPermission = in_array($currentUserId, Vtiger_SharedOwner_UIType::getSharedOwners($id, $recordMetaData['setype']));
								break;
							case 2:
								$permission = isPermittedBySharing($recordMetaData['setype'], \App\Module::getModuleId($recordMetaData['setype']), $actionid, $id);
								$relatedPermission = $permission == 'yes' ? true : false;
								break;
						}
					}
				}
				if ($relatedPermission) {
					$parentRecord = $id;
					break;
				} else if ($type == 2) {
					$rparentRecord = self::getParentRecord($id, $recordMetaData['setype'], $type, $actionid);
					if ($rparentRecord) {
						$parentRecord = $rparentRecord;
					}
				}
			}
		} else if ($relationInfo = \App\ModuleHierarchy::getModulesMapMMCustom($moduleName)) {
			$db = \App\database\PearDatabase::getInstance();
			$role = $userModel->getRoleInstance();
			$query = 'SELECT %s AS crmid FROM `%s` WHERE %s = ?';
			$query = sprintf($query, $relationInfo['rel'], $relationInfo['table'], $relationInfo['base']);
			$result = $db->pquery($query, [$record]);
			while ($row = $db->getRow($result)) {
				$id = $row['crmid'];
				$recordMetaData = \vtlib\Functions::getCRMRecordMetadata($id);
				$permissionsRelatedField = $role->get('permissionsrelatedfield') == '' ? [] : explode(',', $role->get('permissionsrelatedfield'));
				$relatedPermission = false;
				foreach ($permissionsRelatedField as &$row) {
					if (!$relatedPermission) {
						switch ($row) {
							case 0:
								$relatedPermission = $recordMetaData['smownerid'] == $currentUserId || in_array($recordMetaData['smownerid'], $currentUserGroups);
								break;
							case 1:
								$relatedPermission = in_array($currentUserId, Vtiger_SharedOwner_UIType::getSharedOwners($id, $recordMetaData['setype']));
								break;
							case 2:
								$permission = isPermittedBySharing($recordMetaData['setype'], \App\Module::getModuleId($recordMetaData['setype']), $actionid, $id);
								$relatedPermission = $permission == 'yes' ? true : false;
								break;
						}
					}
				}
				if ($relatedPermission) {
					$parentRecord = $id;
					break;
				} else if ($type == 2) {
					$rparentRecord = self::getParentRecord($id, $recordMetaData['setype'], $type, $actionid);
					if ($rparentRecord) {
						$parentRecord = $rparentRecord;
					}
				}
			}
		}
		\App\Cache::staticSave('PrivilegesParentRecord', $cacheKey, $parentRecord);
		return $parentRecord;
	}

	/**
	 * Get profiles ids
	 * @return array
	 */
	public function getProfiles()
	{
		\App\Log::trace('Get profile list');
		return $this->get('profiles');
	}
	
	/**
	 * Get role detail for this user
	 * @return object Role details with get() method for compatibility
	 */
	public function getRoleDetail()
	{
		if ($this->get('roleDetail')) {
			return $this->get('roleDetail');
		}
		$roleId = $this->get('roleid');
		if ($roleId) {
			$db = \App\Db::getInstance();
			$roleData = (new \App\Db\Query())
				->from('vtiger_role')
				->where(['roleid' => $roleId])
				->one($db);
			if ($roleData) {
				// Create simple model with get() method for compatibility
				$roleModel = new class($roleData) {
					private $data;
					public function __construct($data) { $this->data = $data; }
					public function get($key) { return $this->data[$key] ?? null; }
				};
				$this->set('roleDetail', $roleModel);
				return $roleModel;
			}
		}
		return null;
	}
	
	/**
	 * Check if user is admin
	 * @return bool
	 */
	public function isAdminUser()
	{
		return $this->get('is_admin') === 'on' || $this->get('is_admin') === '1' || $this->get('is_admin') === 1;
	}

	/**
	 * Magic getter to handle property access for legacy code compatibility
	 * @param string $property
	 * @return mixed
	 */
	public function __get($property)
	{
		// Handle common user properties accessed as direct properties
		$propertyMap = [
			'time_zone' => 'time_zone',
			'currency_symbol_placement' => 'currency_symbol_placement',
			'date_format' => 'date_format',
			'hour_format' => 'hour_format',
		];

		if (isset($propertyMap[$property])) {
			$value = $this->get($propertyMap[$property]);
			// Return default values if not set
			if ($value === null || $value === '') {
				switch ($property) {
					case 'time_zone':
						return \App\AppConfig::main('default_timezone') ?: 'UTC';
					case 'currency_symbol_placement':
						return '$1.0';
					case 'date_format':
						return 'yyyy-mm-dd';
					case 'hour_format':
						return '24';
					default:
						return '';
				}
			}
			return $value;
		}

		// Fallback to get() method
		return $this->get($property);
	}
}
