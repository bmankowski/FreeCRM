<?php

namespace App\Modules\Settings\Profiles\Models;


/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

/**
 * Profiles Record Model Class
 */



class Record extends \App\Modules\Settings\Base\Models\Record
{

	const PROFILE_FIELD_INACTIVE = 0;
	const PROFILE_FIELD_READONLY = 1;
	const PROFILE_FIELD_READWRITE = 2;

	private static $fieldLockedUiTypes = array('70');

	protected $global_permissions;
	protected $profile_tab_permissions;
	protected $profile_action_permissions;
	protected $profile_utility_permissions;
	protected $profile_tab_field_permissions;
	protected $module_permissions;

	/**
	 * Function to get the Id
	 * @return <Number> Profile Id
	 */
	public function getId()
	{
		return $this->get('profileid');
	}

	/**
	 * Function to get the Id
	 * @return <Number> Profile Id
	 */
	protected function setId($id)
	{
		$this->set('profileid', $id);
		return $this;
	}

	/**
	 * Function to get the Profile Name
	 * @return string
	 */
	public function getName()
	{
		return $this->get('profilename');
	}

	/**
	 * Function to get the description of the Profile
	 * @return string
	 */
	public function getDescription()
	{
		return $this->get('description');
	}

	/**
	 * Function to get the Edit View Url for the Profile
	 * @return string
	 */
	public function getEditViewUrl()
	{
		return '?module=Profiles&parent=Settings&view=Edit&record=' . $this->getId();
	}

	/**
	 * Function to get the Edit View Url for the Profile
	 * @return string
	 */
	public function getDuplicateViewUrl()
	{
		return '?module=Profiles&parent=Settings&view=Edit&from_record=' . $this->getId();
	}

	/**
	 * Function to get the Detail Action Url for the Profile
	 * @return string
	 */
	public function getDeleteAjaxUrl()
	{
		return '?module=Profiles&parent=Settings&action=DeleteAjax&record=' . $this->getId();
	}

	/**
	 * Function to get the Delete Action Url for the current profile
	 * @return string
	 */
	public function getDeleteActionUrl()
	{
		return 'index.php?module=Profiles&parent=Settings&view=DeleteAjax&record=' . $this->getId();
	}

	public function getGlobalPermissions()
	{
		$db = \App\Database\PearDatabase::getInstance();

		if (!isset($this->global_permissions)) {
			$globalPermissions = [];
			$globalPermissions[\App\Modules\Settings\Profiles\Models\Module::GLOBAL_ACTION_VIEW] = $globalPermissions[\App\Modules\Settings\Profiles\Models\Module::GLOBAL_ACTION_EDIT] = \App\Modules\Settings\Profiles\Models\Module::GLOBAL_ACTION_DEFAULT_VALUE;

			if ($this->getId()) {
				$sql = 'SELECT * FROM vtiger_profile2globalpermissions WHERE profileid=?';
				$params = array($this->getId());
				$result = $db->pquery($sql, $params);
				$noOfRows = $db->num_rows($result);
				for ($i = 0; $i < $noOfRows; ++$i) {
					$actionId = $db->query_result($result, $i, 'globalactionid');
					$permissionId = $db->query_result($result, $i, 'globalactionpermission');
					$globalPermissions[$actionId] = $permissionId;
				}
			}
			$this->global_permissions = $globalPermissions;
		}
		return $this->global_permissions;
	}

	public function hasGlobalReadPermission()
	{
		$globalPermissions = $this->getGlobalPermissions();
		$viewAllPermission = $globalPermissions[\App\Modules\Settings\Profiles\Models\Module::GLOBAL_ACTION_VIEW];
		if ($viewAllPermission == \App\Modules\Settings\Profiles\Models\Module::IS_PERMITTED_VALUE) {
			return true;
		}
		return false;
	}

	public function hasGlobalWritePermission()
	{
		$globalPermissions = $this->getGlobalPermissions();
		$editAllPermission = $globalPermissions[\App\Modules\Settings\Profiles\Models\Module::GLOBAL_ACTION_EDIT];
		if (
			$this->hasGlobalReadPermission() &&
			$editAllPermission == \App\Modules\Settings\Profiles\Models\Module::IS_PERMITTED_VALUE
		) {
			return true;
		}
		return false;
	}

	public function hasModulePermission($module)
	{
		$moduleModule = $this->getProfileTabModel($module);
		$modulePermissions = $moduleModule->get('permissions');
		$moduleAccessPermission = $modulePermissions['is_permitted'];
		if (isset($modulePermissions['is_permitted']) && $moduleAccessPermission == \App\Modules\Settings\Profiles\Models\Module::IS_PERMITTED_VALUE) {
			return true;
		}
		return false;
	}

	public function hasModuleActionPermission($module, $action)
	{
		$actionId = false;
		if (is_object($action) && is_a($action, '\App\Modules\Base\Models\Action')) {
			$actionId = $action->getId();
		} else {
			$action = \App\Modules\Base\Models\Action::getInstance($action);
			$actionId = $action->getId();
		}
		if (!$actionId) {
			return false;
		}

		$moduleModel = $this->getProfileTabModel($module);
		$modulePermissions = $moduleModel->get('permissions');
		$moduleAccessPermission = $modulePermissions['is_permitted'];
		if ($moduleAccessPermission != \App\Modules\Settings\Profiles\Models\Module::IS_PERMITTED_VALUE) {
			return false;
		}
		$moduleActionPermissions = $modulePermissions['actions'];
		$moduleActionPermission = $moduleActionPermissions[$actionId];
		if (isset($moduleActionPermissions[$actionId]) && $moduleActionPermission == \App\Modules\Settings\Profiles\Models\Module::IS_PERMITTED_VALUE) {
			return true;
		}
		return false;
	}

	public function hasModuleFieldPermission($module, $field)
	{
		$fieldModel = $this->getProfileTabFieldModel($module, $field);
		$fieldPermissions = $fieldModel->get('permissions');
		$fieldAccessPermission = $fieldPermissions['visible'];
		if ($fieldModel->isViewEnabled() && $fieldAccessPermission == \App\Modules\Settings\Profiles\Models\Module::IS_PERMITTED_VALUE) {
			return true;
		}
		return false;
	}

	public function hasModuleFieldWritePermission($module, $field)
	{
		$fieldModel = $this->getProfileTabFieldModel($module, $field);
		$fieldPermissions = $fieldModel->get('permissions');
		$fieldAccessPermission = $fieldPermissions['visible'];
		$fieldReadOnlyPermission = $fieldPermissions['readonly'];
		if ($fieldModel->isWritable() && $fieldAccessPermission == \App\Modules\Settings\Profiles\Models\Module::IS_PERMITTED_VALUE && $fieldReadOnlyPermission == \App\Modules\Settings\Profiles\Models\Module::IS_PERMITTED_VALUE) {
			return true;
		}
		return false;
	}

	public function getModuleFieldPermissionValue($module, $field)
	{
		if (!$this->hasModuleFieldPermission($module, $field)) {
			return self::PROFILE_FIELD_INACTIVE;
		} elseif ($this->hasModuleFieldWritePermission($module, $field)) {
			return self::PROFILE_FIELD_READWRITE;
		} else {
			return self::PROFILE_FIELD_READONLY;
		}
	}

	public function isModuleFieldLocked($module, $field)
	{
		$fieldModel = $this->getProfileTabFieldModel($module, $field);
		if (!$fieldModel->isEditable() || $fieldModel->isMandatory() || in_array($fieldModel->get('uitype'), self::$fieldLockedUiTypes)) {
			return true;
		}
		return false;
	}

	public function getProfileTabModel($module)
	{
		$tabId = false;
		if (is_object($module) && is_a($module, '\App\Modules\Base\Models\Module')) {
			$tabId = $module->getId();
		} else {
			$module = \App\Modules\Base\Models\Module::getInstance($module);
			$tabId = $module->getId();
		}
		if (!$tabId) {
			return false;
		}
		$allModulePermissions = $this->getModulePermissions();
		$moduleModel = $allModulePermissions[$tabId];
		return $moduleModel;
	}

	public function getProfileTabFieldModel($module, $field)
	{
		$profileTabModel = $this->getProfileTabModel($module);
		$fieldId = false;
		if (is_object($field) && is_a($field, '\App\Modules\Base\Models\Field')) {
			$fieldId = $field->getId();
		} else {
			$field = \App\Modules\Base\Models\Field::getInstance($field, $profileTabModel);
			$fieldId = $field->getId();
		}
		if (!$fieldId) {
			return false;
		}
		$moduleFields = $profileTabModel->getFields();
		$fieldModel = $moduleFields[$field->getName()];
		return $fieldModel;
	}

	public function getProfileTabPermissions()
	{
		$db = \App\Database\PearDatabase::getInstance();

		if (!isset($this->profile_tab_permissions)) {
			$profile2TabPermissions = [];
			if ($this->getId()) {
				$result = $db->pquery('SELECT * FROM vtiger_profile2tab WHERE profileid=?', [$this->getId()]);
				while ($row = $db->getRow($result)) {
					$profile2TabPermissions[$row['tabid']] = $row['permissions'];
				}
			}
			$this->profile_tab_permissions = $profile2TabPermissions;
		}
		return $this->profile_tab_permissions;
	}

	public function getProfileTabFieldPermissions($tabId)
	{
		if (!isset($this->profile_tab_field_permissions[$tabId])) {
			$profile2TabFieldPermissions = [];
			if ($this->getId()) {
				$dataReader = (new \App\Db\Query())->from('vtiger_profile2field')
					->where(['profileid' => $this->getId(), 'tabid' => $tabId])
					->createCommand()->query();
				while ($row = $dataReader->read()) {
					$fieldId = $row['fieldid'];
					$visible = $row['visible'];
					$readOnly = $row['readonly'];
					$profile2TabFieldPermissions[$fieldId]['visible'] = $visible;
					$profile2TabFieldPermissions[$fieldId]['readonly'] = $readOnly;
				}
			}
			$this->profile_tab_field_permissions[$tabId] = $profile2TabFieldPermissions;
		}
		return $this->profile_tab_field_permissions[$tabId];
	}

	public function getProfileActionPermissions()
	{
		$db = \App\Database\PearDatabase::getInstance();

		if (!isset($this->profile_action_permissions)) {
			$profile2ActionPermissions = [];
			if ($this->getId()) {
				$sql = 'SELECT * FROM vtiger_profile2standardpermissions WHERE profileid=?';
				$params = array($this->getId());
				$result = $db->pquery($sql, $params);
				$noOfRows = $db->num_rows($result);
				for ($i = 0; $i < $noOfRows; ++$i) {
					$tabId = $db->query_result($result, $i, 'tabid');
					$operation = $db->query_result($result, $i, 'operation');
					$permissionId = $db->query_result($result, $i, 'permissions');
					$profile2ActionPermissions[$tabId][$operation] = $permissionId;
				}
			}
			$this->profile_action_permissions = $profile2ActionPermissions;
		}
		return $this->profile_action_permissions;
	}

	public function getProfileUtilityPermissions()
	{
		$db = \App\Database\PearDatabase::getInstance();

		if (!isset($this->profile_utility_permissions)) {
			$profile2UtilityPermissions = [];
			if ($this->getId()) {
				$sql = 'SELECT * FROM vtiger_profile2utility WHERE profileid=?';
				$params = array($this->getId());
				$result = $db->pquery($sql, $params);
				$noOfRows = $db->num_rows($result);
				for ($i = 0; $i < $noOfRows; ++$i) {
					$tabId = $db->query_result($result, $i, 'tabid');
					$utility = $db->query_result($result, $i, 'activityid');
					$permissionId = $db->query_result($result, $i, 'permission');
					$profile2UtilityPermissions[$tabId][$utility] = $permissionId;
				}
			}
			$this->profile_utility_permissions = $profile2UtilityPermissions;
		}
		return $this->profile_utility_permissions;
	}

	public function getModulePermissions()
	{
		if (!isset($this->module_permissions)) {
			$allModules = \App\Modules\Base\Models\Module::getAll(array(0), \App\Modules\Settings\Profiles\Models\Module::getNonVisibleModulesList());
			$eventModule = \App\Modules\Base\Models\Module::getInstance('Events');
			$allModules[$eventModule->getId()] = $eventModule;
			$profileTabPermissions = $this->getProfileTabPermissions();
			$profileActionPermissions = $this->getProfileActionPermissions();
			$profileUtilityPermissions = $this->getProfileUtilityPermissions();
			$allTabActions = \App\Modules\Base\Models\Action::getAll(true);

			foreach ($allModules as $id => $moduleModel) {
				$permissions = [];
				$permissions['is_permitted'] = \App\Modules\Settings\Profiles\Models\Module::IS_PERMITTED_VALUE;
				if (isset($profileTabPermissions[$id])) {
					$permissions['is_permitted'] = $profileTabPermissions[$id];
				}
				$permissions['actions'] = [];
				foreach ($allTabActions as $actionModel) {
					$actionId = $actionModel->getId();
					if (isset($profileActionPermissions[$id][$actionId])) {
						$permissions['actions'][$actionId] = $profileActionPermissions[$id][$actionId];
					} elseif (isset($profileUtilityPermissions[$id][$actionId])) {
						$permissions['actions'][$actionId] = $profileUtilityPermissions[$id][$actionId];
					} else {
						$permissions['actions'][$actionId] = \App\Modules\Settings\Profiles\Models\Module::NOT_PERMITTED_VALUE;
					}
				}
				$moduleFields = $moduleModel->getFields();
				$allFieldPermissions = $this->getProfileTabFieldPermissions($id);
				foreach ($moduleFields as $fieldName => $fieldModel) {
					$fieldPermissions = [];
					$fieldId = $fieldModel->getId();
					$fieldPermissions['visible'] = \App\Modules\Settings\Profiles\Models\Module::IS_PERMITTED_VALUE;
					if (isset($allFieldPermissions[$fieldId]['visible'])) {
						$fieldPermissions['visible'] = $allFieldPermissions[$fieldId]['visible'];
					}
					$fieldPermissions['readonly'] = \App\Modules\Settings\Profiles\Models\Module::IS_PERMITTED_VALUE;
					if (isset($allFieldPermissions[$fieldId]['readonly'])) {
						$fieldPermissions['readonly'] = $allFieldPermissions[$fieldId]['readonly'];
					}
					$fieldModel->set('permissions', $fieldPermissions);
				}
				$moduleModel->set('permissions', $permissions);
			}
			$this->module_permissions = $allModules;
		}
		return $this->module_permissions;
	}

	public function delete($transferToRecord)
	{
		$db = \App\Database\PearDatabase::getInstance();
		$profileId = $this->getId();
		$transferProfileId = $transferToRecord->getId();

		$db->pquery('DELETE FROM vtiger_profile2globalpermissions WHERE profileid=?', array($profileId));
		$db->pquery('DELETE FROM vtiger_profile2tab WHERE profileid=?', array($profileId));
		$db->pquery('DELETE FROM vtiger_profile2standardpermissions WHERE profileid=?', array($profileId));
		$db->pquery('DELETE FROM vtiger_profile2utility WHERE profileid=?', array($profileId));
		$db->pquery('DELETE FROM vtiger_profile2field WHERE profileid=?', array($profileId));

		$checkSql = 'SELECT roleid, count(profileid) AS profilecount FROM vtiger_role2profile
							WHERE roleid IN (select roleid FROM vtiger_role2profile WHERE profileid=?) GROUP BY roleid';
		$checkParams = array($profileId);
		$checkResult = $db->pquery($checkSql, $checkParams);
		$noOfRoles = $db->num_rows($checkResult);
		for ($i = 0; $i < $noOfRoles; ++$i) {
			$roleId = $db->query_result($checkResult, $i, 'roleid');
			$profileCount = $db->query_result($checkResult, $i, 'profilecount');
			if ($profileCount > 1) {
				$sql = 'DELETE FROM vtiger_role2profile WHERE roleid=? && profileid=?';
				$params = array($roleId, $profileId);
			} else {
				$sql = 'UPDATE vtiger_role2profile SET profileid=? WHERE roleid=? && profileid=?';
				$params = array($transferProfileId, $roleId, $profileId);
			}
			$db->pquery($sql, $params);
		}

		$db->pquery('DELETE FROM vtiger_profile WHERE profileid=?', array($profileId));
		\App\Modules\Users\Services\PrivilegeFileManager::RecalculateSharingRules();
	}

	public function save($request = null)
	{
		$adb = \App\Db\Db::getInstance();
		$db = \App\Database\PearDatabase::getInstance();
		$modulePermissions = $this->getModulePermissions();

		$profileName = $this->get('profilename');
		$description = $this->get('description');
		$profilePermissions = $this->get('profile_permissions');
		$calendarModule = \App\Modules\Base\Models\Module::getInstance('Calendar');
		$eventModule = \App\Modules\Base\Models\Module::getInstance('Events');
		$eventFieldsPermissions = $profilePermissions[$eventModule->getId()]['fields'];
		$profilePermissions[$eventModule->getId()] = $profilePermissions[$calendarModule->getId()];
		$profilePermissions[$eventModule->getId()]['fields'] = $eventFieldsPermissions;

		$isProfileDirectlyRelatedToRole = 0;
		$isNewProfile = false;
		if ($this->has('directly_related_to_role')) {
			$isProfileDirectlyRelatedToRole = $this->get('directly_related_to_role');
		}
		$profileId = $this->getId();
		if (!$profileId) {
			$adb->createCommand()->insert('vtiger_profile', [
				'profilename' => $profileName,
				'description' => $description,
				'directly_related_to_role' => $isProfileDirectlyRelatedToRole
			])->execute();
			$profileId = $adb->getLastInsertID('vtiger_profile_profileid_seq');
			$this->setId($profileId);
			$isNewProfile = true;
		} else {
			$sql = 'UPDATE vtiger_profile SET profilename=?, description=?, directly_related_to_role=? WHERE profileid=?';
			$params = array($profileName, $description, $isProfileDirectlyRelatedToRole, $profileId);
			$db->pquery('DELETE FROM vtiger_profile2globalpermissions WHERE profileid=?', array($profileId));
			$db->pquery($sql, $params);
		}
		$sql = 'INSERT INTO vtiger_profile2globalpermissions(profileid, globalactionid, globalactionpermission) VALUES (?,?,?)';
		$params = array($profileId, \App\Modules\Settings\Profiles\Models\Module::GLOBAL_ACTION_VIEW, $this->tranformInputPermissionValue($this->get('viewall')));
		$db->pquery($sql, $params);

		$sql = 'INSERT INTO vtiger_profile2globalpermissions(profileid, globalactionid, globalactionpermission) VALUES (?,?,?)';
		$params = array($profileId, \App\Modules\Settings\Profiles\Models\Module::GLOBAL_ACTION_EDIT, $this->tranformInputPermissionValue($this->get('editall')));
		$db->pquery($sql, $params);

		$allModuleModules = \App\Modules\Base\Models\Module::getAll(array(0), \App\Modules\Settings\Profiles\Models\Module::getNonVisibleModulesList());
		$allModuleModules[$eventModule->getId()] = $eventModule;
		if (count($allModuleModules) > 0) {
			$actionModels = \App\Modules\Base\Models\Action::getAll(true);
			foreach ($allModuleModules as $tabId => $moduleModel) {
				if ($moduleModel->isActive() && isset($profilePermissions[$moduleModel->getId()])) {
					$this->saveModulePermissions($moduleModel, $profilePermissions[$moduleModel->getId()]);
				} else {
					$permissions = [];
					$permissions['is_permitted'] = \App\Modules\Settings\Profiles\Models\Module::IS_PERMITTED_VALUE;
					if ($moduleModel->isEntityModule()) {
						$permissions['actions'] = [];
						foreach ($actionModels as $actionModel) {
							if ($actionModel->isModuleEnabled($moduleModel)) {
								$permissions['actions'][$actionModel->getId()] = \App\Modules\Settings\Profiles\Models\Module::IS_PERMITTED_VALUE;
							}
						}
						$permissions['fields'] = [];
						$moduleFields = $moduleModel->getFields();
						foreach ($moduleFields as $fieldModel) {
							if ($fieldModel->isWritable()) {
								$permissions['fields'][$fieldModel->getId()] = \App\Modules\Settings\Profiles\Models\Record::PROFILE_FIELD_READWRITE;
							} elseif ($fieldModel->isViewEnabled()) {
								$permissions['fields'][$fieldModel->getId()] = \App\Modules\Settings\Profiles\Models\Record::PROFILE_FIELD_READONLY;
							} else {
								$permissions['fields'][$fieldModel->getId()] = \App\Modules\Settings\Profiles\Models\Record::PROFILE_FIELD_INACTIVE;
							}
						}
					}
					$this->saveModulePermissions($moduleModel, $permissions);
				}
			}
		}
		if ($isNewProfile) {
			$this->saveUserAccessbleFieldsIntoProfile2Field();
		}

		$this->recalculate();
		return $profileId;
	}

	protected function saveModulePermissions($moduleModel, $permissions)
	{
		$db = \App\Database\PearDatabase::getInstance();
		$adb = \App\Db\Db::getInstance();
		$profileId = $this->getId();
		$tabId = $moduleModel->getId();
		$profileUtilityPermissions = $this->getProfileUtilityPermissions();
		$profileTabPermissionsBase = $this->getProfileTabPermissions();
		$profileTabPermissions = isset($profileTabPermissionsBase[$tabId]) ? $profileTabPermissionsBase[$tabId] : false;
		$profileActionPermissions = $this->getProfileActionPermissions();
		$profileActionPermissions = isset($profileActionPermissions[$tabId]) ? $profileActionPermissions[$tabId] : false;
		$adb->createCommand()->delete('vtiger_profile2tab', ['profileid' => $profileId, 'tabid' => $tabId])
			->execute();
		$actionPermissions = [];
		$actionEnabled = false;
		if ($moduleModel->isEntityModule() || $moduleModel->isUtilityActionEnabled()) {
			if (isset($permissions['actions']) || $moduleModel->isUtilityActionEnabled()) {
				$actionPermissions = isset($permissions['actions']) ? $permissions['actions'] : [];
				$actionsIdsList = \App\Modules\Base\Models\Action::$standardActions;
				//Dividing on actions
				$utilityIdsList = [];
				foreach ($actionPermissions as $actionId => $permission) {
					if (isset($actionsIdsList[$actionId])) {
						$actionsIdsList[$actionId] = $permission;
					} else {
						$utilityIdsList[$actionId] = $permission;
					}
				}
				//Update process
				if ($profileActionPermissions || isset($profileTabPermissionsBase[$tabId]) || isset($profileUtilityPermissions[$tabId])) {
					//Standard permissions
					if (!$moduleModel->isEntityModule()) {
						$actionEnabled = true;
					} elseif ($actionsIdsList) {
						$actionsUpdateQuery = 'UPDATE vtiger_profile2standardpermissions SET permissions = CASE ';
						foreach ($actionsIdsList as $actionId => $permission) {
							if (in_array($permission, \App\Modules\Base\Models\Action::$nonConfigurableActions)) {
								$permission = 'on';
							}
							$permissionValue = $this->tranformInputPermissionValue($permission);
							if (isset(\App\Modules\Base\Models\Action::$standardActions[$actionId])) {
								if ($permission == \App\Modules\Settings\Profiles\Models\Module::IS_PERMITTED_VALUE) {
									$actionEnabled = true;
								}
								$actionsUpdateQuery .= " WHEN operation = $actionId THEN $permissionValue ";
							}
						}
						$actionsUpdateQuery .= 'ELSE permissions END WHERE profileid = ? AND tabid = ?';
						$db->pquery($actionsUpdateQuery, [$profileId, $tabId]);
					}

					foreach (\App\Modules\Base\Models\Action::$utilityActions as $utilityActionId => $utilityActionName) {
						if (!isset($utilityIdsList[$utilityActionId])) {
							$utilityIdsList[$utilityActionId] = 'off';
						}
					}
					//Utility permissions
					if ($utilityIdsList) {
						$actionEnabled = true;
						$utilityUpdateQuery = 'UPDATE vtiger_profile2utility SET permission = CASE ';
						foreach ($utilityIdsList as $actionId => $permission) {
							$permissionValue = $this->tranformInputPermissionValue($permission);
							$utilityUpdateQuery .= " WHEN activityid = $actionId THEN $permissionValue ";
						}
						$utilityUpdateQuery .= 'ELSE ? END WHERE profileid = ? AND tabid = ?';
						$db->pquery($utilityUpdateQuery, [1, $profileId, $tabId]);
					}
				} else {
					//Insert Process
					//Standard permissions
					$i = 0;
					$count = count($actionsIdsList);
					$actionsInsertQuery = 'INSERT INTO vtiger_profile2standardpermissions(profileid, tabid, operation, permissions) VALUES ';
					foreach ($actionsIdsList as $actionId => $permission) {
						if (in_array($permission, \App\Modules\Base\Models\Action::$nonConfigurableActions)) {
							$permission = 'on';
						}
						$actionEnabled = true;
						$permissionValue = $this->tranformInputPermissionValue($permission);
						$actionsInsertQuery .= "($profileId, $tabId, $actionId, $permissionValue)";

						if ($i !== $count - 1) {
							$actionsInsertQuery .= ', ';
						}
						$i++;
					}
					if ($actionsIdsList && ($moduleModel->isEntityModule())) {
						$db->query($actionsInsertQuery);
					}

					//Utility permissions
					$i = 0;
					$count = count($utilityIdsList);
					$utilityInsertQuery = 'INSERT INTO vtiger_profile2utility(profileid, tabid, activityid, permission) VALUES ';
					foreach ($utilityIdsList as $actionId => $permission) {
						$permissionValue = $this->tranformInputPermissionValue($permission);
						$utilityInsertQuery .= "($profileId, $tabId, $actionId, $permissionValue)";

						if ($i !== $count - 1) {
							$utilityInsertQuery .= ', ';
						}
						$i++;
					}
					if ($utilityIdsList) {
						$actionEnabled = true;
						$db->pquery($utilityInsertQuery, []);
					}
				}
			}
		} else {
			$actionEnabled = true;
		}

		// Enable module permission in profile2tab table only if either its an extension module or the entity module has atleast 1 action enabled
		if ($actionEnabled && isset($permissions['is_permitted'])) {
			$isModulePermitted = $this->tranformInputPermissionValue($permissions['is_permitted']);
		} else {
			$isModulePermitted = \App\Modules\Settings\Profiles\Models\Module::NOT_PERMITTED_VALUE;
		}
		if ($isModulePermitted != $profileTabPermissions) {
			\App\Security\Privilege::setUpdater($moduleModel->getName());
		}
		$adb->createCommand()->insert('vtiger_profile2tab', [
			'profileid' => $profileId,
			'tabid' => $tabId,
			'permissions' => $isModulePermitted
		])->execute();
		if (isset($permissions['fields'])) {
			if (is_array($permissions['fields'])) {
				foreach ($permissions['fields'] as $fieldId => $stateValue) {
					$adb->createCommand()->delete('vtiger_profile2field', ['profileid' => $profileId, 'tabid' => $tabId, 'fieldid' => $fieldId])
						->execute();
					if ($stateValue == \App\Modules\Settings\Profiles\Models\Record::PROFILE_FIELD_INACTIVE) {
						$visible = \App\Modules\Settings\Profiles\Models\Module::FIELD_INACTIVE;
						$readOnly = \App\Modules\Settings\Profiles\Models\Module::IS_PERMITTED_VALUE;
					} elseif ($stateValue == \App\Modules\Settings\Profiles\Models\Record::PROFILE_FIELD_READONLY) {
						$visible = \App\Modules\Settings\Profiles\Models\Module::FIELD_ACTIVE;
						$readOnly = \App\Modules\Settings\Profiles\Models\Module::FIELD_READONLY;
					} else {
						$visible = \App\Modules\Settings\Profiles\Models\Module::FIELD_ACTIVE;
						$readOnly = \App\Modules\Settings\Profiles\Models\Module::FIELD_READWRITE;
					}
					$adb->createCommand()->insert('vtiger_profile2field', [
						'profileid' => $profileId,
						'tabid' => $tabId,
						'fieldid' => $fieldId,
						'visible' => $visible,
						'readonly' => $readOnly
					])->execute();
				}
			}
		}
	}

	protected function tranformInputPermissionValue($value)
	{
		if ($value === 'on' || $value === '1') {
			return \App\Modules\Settings\Profiles\Models\Module::IS_PERMITTED_VALUE;
		} else {
			return \App\Modules\Settings\Profiles\Models\Module::NOT_PERMITTED_VALUE;
		}
	}

	/**
	 * Function to get the list view actions for the record
	 * @return <Array> - Associate array of \App\Modules\Base\Models\Link instances
	 */
	public function getRecordLinks()
	{

		$links = [];

		$recordLinks = array(
			array(
				'linktype' => 'LISTVIEWRECORD',
				'linklabel' => 'LBL_EDIT_RECORD',
				'linkurl' => $this->getEditViewUrl(),
				'linkicon' => 'glyphicon glyphicon-pencil'
			),
			array(
				'linktype' => 'LISTVIEWRECORD',
				'linklabel' => 'LBL_DUPLICATE_RECORD',
				'linkurl' => $this->getDuplicateViewUrl(),
				'linkicon' => 'icon-share'
			),
			array(
				'linktype' => 'LISTVIEWRECORD',
				'linklabel' => 'LBL_DELETE_RECORD',
				'linkurl' => "javascript:Settings_Vtiger_ListView_Js.triggerDelete(event,'" . $this->getDeleteActionUrl() . "')",
				'linkicon' => 'glyphicon glyphicon-trash'
			)
		);
		foreach ($recordLinks as $recordLink) {
			$links[] = \App\Modules\Base\Models\Link::getInstanceFromValues($recordLink);
		}

		return $links;
	}

	/**
	 * Function to get all the profiles linked to the given role
	 * @param string - $roleId
	 * @return <Array> - Array of \App\Modules\Settings\Profiles\Models\Record instances
	 */
	public static function getAllByRole($roleId)
	{
		$db = \App\Database\PearDatabase::getInstance();

		$sql = 'SELECT vtiger_profile.*
					FROM vtiger_profile
					INNER JOIN
						vtiger_role2profile ON vtiger_profile.profileid = vtiger_role2profile.profileid
						AND
						vtiger_role2profile.roleid = ?';
		$params = array($roleId);
		$result = $db->pquery($sql, $params);
		$profiles = [];
		while ($row = $db->getRow($result)) {
			$profile = new self();
			$profile->setData($row);
			$profiles[$profile->getId()] = $profile;
		}
		return $profiles;
	}

	/**
	 * Function to get all the profiles
	 * @return <Array> - Array of \App\Modules\Settings\Profiles\Models\Record instances
	 */
	public static function getAll()
	{
		$db = \App\Database\PearDatabase::getInstance();
		$sql = 'SELECT * FROM vtiger_profile';
		$result = $db->query($sql);
		$profiles = [];
		while ($row = $db->getRow($result)) {
			$profile = new self();
			$profile->setData($row);
			$profiles[$profile->getId()] = $profile;
		}
		return $profiles;
	}

	/**
	 * Function to get the instance of Profile model, given profile id
	 * @param <Integer> $profileId
	 * @return \App\Modules\Settings\Profiles\Models\Record instance, if exists. Null otherwise
	 */
	public static function getInstanceById($profileId)
	{
		$instance = \App\Cache\Cache::get('ProfilesRecordModelById', $profileId);
		if ($instance) {
			return $instance;
		}

		$db = \App\Database\PearDatabase::getInstance();
		$sql = 'SELECT * FROM vtiger_profile WHERE profileid = ?';
		$result = $db->pquery($sql, [$profileId]);
		if ($db->getRowCount($result) > 0) {
			$row = $db->getRow($result);
			$profile = new self();
			$profile->setData($row);
		}
		\App\Cache\Cache::save('ProfilesRecordModelById', $profileId, $profile);
		return $profile;
	}

	public static function getInstanceByName($profileName, $checkOnlyDirectlyRelated = false, $excludedRecordId = [])
	{
		$query = (new \App\Db\Query())->from('vtiger_profile')->where(['profilename' => $profileName]);
		if ($checkOnlyDirectlyRelated) {
			$query->andWhere(['directly_related_to_role' => 1]);
		}
		if (!empty($excludedRecordId)) {
			$query->andWhere(['NOT IN', 'profileid', $excludedRecordId]);
		}
		$row = $query->one();
		if ($row) {
			$profile = new self();
			$profile->setData($row);
			return $profile;
		}
		return null;
	}

	/**
	 * Function to get the Detail Url for the current group
	 * @return string
	 */
	public function getDetailViewUrl()
	{
		return '?module=Profiles&parent=Settings&view=Detail&record=' . $this->getId();
	}

	/**
	 * Function to check whether the profiles is directly related to role
	 * @return Boolean
	 */
	public function isDirectlyRelated()
	{
		$isDirectlyRelated = $this->get('directly_related_to_role');
		if ($isDirectlyRelated == 1) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Function recalculate the sharing rules
	 */
	public function recalculate()
	{
		$php_max_execution_time = \App\Core\AppConfig::main('php_max_execution_time');
		set_time_limit($php_max_execution_time);


		$userIdsList = self::getUsersList($this->getId());
		if ($userIdsList) {
			foreach ($userIdsList as $userId) {
				\App\Modules\Users\Services\PrivilegeFileManager::invalidateUser((int) $userId, 'Settings\Profiles\Models\Record::recalculate', false);
			}
		}
	}

	/**
	 * Function to get Users list from this Profile
	 * @param boolean $allUsers
	 * @return <Array> list of user ids
	 */
	public static function getUsersList($profileId = false)
	{
		$db = \App\Database\PearDatabase::getInstance();
		$params = [0];
		$query = 'SELECT id FROM vtiger_users
					INNER JOIN vtiger_user2role ON vtiger_user2role.userid = vtiger_users.id
					INNER JOIN vtiger_role2profile ON vtiger_role2profile.roleid = vtiger_user2role.roleid
					WHERE vtiger_users.deleted = ?';

		if ($profileId) {
			$query .= ' AND vtiger_role2profile.profileid = ?';
			$params[] = $profileId;
		}
		$result = $db->pquery($query, $params);
		return $db->getArrayColumn($result);
	}

	/**
	 * Function to save user fields in vtiger_profile2field table
	 * We need user field values to generating the Email Templates variable valuues.
	 * @param type $profileId
	 */
	public function saveUserAccessbleFieldsIntoProfile2Field()
	{
		$profileId = $this->getId();
		if (!empty($profileId)) {
			$db = \App\Database\PearDatabase::getInstance();
			$userRecordModel = \App\User\CurrentUser::get();
			$module = $userRecordModel->getModuleName();
			$tabId = \App\Utils\ModuleUtils::getModuleId($module);
			$userModuleModel = \App\Modules\Users\Models\Module::getInstance($module);
			$moduleFields = $userModuleModel->getFields();

			$userAccessbleFields = [];
			$skipFields = array(98, 115, 116, 31, 32);
			foreach ($moduleFields as $fieldName => $fieldModel) {
				if ($fieldModel->getFieldDataType() == 'string' || $fieldModel->getFieldDataType() == 'email' || $fieldModel->getFieldDataType() == 'phone') {
					if (!in_array($fieldModel->get('uitype'), $skipFields) && $fieldName != 'asterisk_extension') {
						if (!isset($userAccessbleFields[$fieldModel->get('id')])) {
							$userAccessbleFields[$fieldModel->get('id')] = $fieldName;
						} else {
							$userAccessbleFields[$fieldModel->get('id')] .= $fieldName;
						}
					}
				}
			}

			//Added user fields into vtiger_profile2field and vtiger_def_org_field
			//We are using this field information in Email Templates.
			foreach ($userAccessbleFields as $fieldId => $fieldName) {
				$insertQuery = 'INSERT INTO vtiger_profile2field VALUES(?,?,?,?,?)';
				$db->pquery($insertQuery, array($profileId, $tabId, $fieldId, \App\Modules\Settings\Profiles\Models\Module::FIELD_ACTIVE, \App\Modules\Settings\Profiles\Models\Module::FIELD_READWRITE));
			}

			$sql = 'SELECT fieldid FROM vtiger_def_org_field WHERE tabid = ?';
			$result1 = $db->pquery($sql, array($tabId));
			$def_org_fields = [];
			for ($j = 0; $j < $db->num_rows($result1); $j++) {
				array_push($def_org_fields, $db->query_result($result1, $j, 'fieldid'));
			}
			foreach ($userAccessbleFields as $fieldId => $fieldName) {
				if (!in_array($fieldId, $def_org_fields)) {
					$insertQuery = 'INSERT INTO vtiger_def_org_field VALUES(?,?,?,?)';
					$db->pquery($insertQuery, array($tabId, $fieldId, 0, 0));
				}
			}
		}
	}

	/**
	 * Function to get the Profile Global Information for the specified vtiger_profileid
	 * @param int $profileid Profile Id
	 * @return array Profile Global Permission Array in the following format:
	 * $profileGlobalPermission=Array($viewall_actionid=>permission, $editall_actionid=>permission)
	 */
	public static function getProfileGlobalPermission($profileid)
	{
		\App\Log\Log::trace("Entering getProfileGlobalPermission(" . $profileid . ") method ...");
		$adb = \App\Database\PearDatabase::getInstance();
		$sql = "select * from vtiger_profile2globalpermissions where profileid=?";
		$result = $adb->pquery($sql, array($profileid));
		$num_rows = $adb->num_rows($result);

		$copy = [];
		for ($i = 0; $i < $num_rows; $i++) {
			$act_id = $adb->query_result($result, $i, "globalactionid");
			$per_id = $adb->query_result($result, $i, "globalactionpermission");
			$copy[$act_id] = $per_id;
		}

		\App\Log\Log::trace("Exiting getProfileGlobalPermission method ...");
		return $copy;
	}

	/**
	 * Function to get the Profile Tab Permissions for the specified vtiger_profileid
	 * @param int $profileid Profile Id
	 * @return array Profile Tabs Permission Array in the following format:
	 * $profileTabPermission=Array($tabid1=>permission, $tabid2=>permission,........., $tabidn=>permission)
	 */
	public static function getProfileTabsPermission($profileid)
	{
		\App\Log\Log::trace("Entering getProfileTabsPermission(" . $profileid . ") method ...");
		$adb = \App\Database\PearDatabase::getInstance();
		$sql = "select * from vtiger_profile2tab where profileid=?";
		$result = $adb->pquery($sql, array($profileid));
		$num_rows = $adb->num_rows($result);

		$copy = [];
		for ($i = 0; $i < $num_rows; $i++) {
			$tab_id = $adb->query_result($result, $i, "tabid");
			$per_id = $adb->query_result($result, $i, "permissions");
			$copy[$tab_id] = $per_id;
		}

		\App\Log\Log::trace("Exiting getProfileTabsPermission method ...");
		return $copy;
	}

	/**
	 * Function to get the Profile Action Permissions for the specified vtiger_profileid
	 * @param int $profileid Profile Id
	 * @return array Profile Tabs Action Permission Array in the following format:
	 *    $tabActionPermission = Array($tabid1=>Array(actionid1=>permission, actionid2=>permission,...,actionidn=>permission),
	 *                        $tabid2=>Array(actionid1=>permission, actionid2=>permission,...,actionidn=>permission),
	 *                                |
	 *                        $tabidn=>Array(actionid1=>permission, actionid2=>permission,...,actionidn=>permission))
	 */
	public static function getProfileActionPermission($profileid)
	{
		\App\Log\Log::trace("Entering getProfileActionPermission(" . $profileid . ") method ...");
		$adb = \App\Database\PearDatabase::getInstance();
		$check = [];
		$temp_tabid = [];
		$sql1 = "select * from vtiger_profile2standardpermissions where profileid=?";
		$result1 = $adb->pquery($sql1, array($profileid));
		$num_rows1 = $adb->num_rows($result1);
		for ($i = 0; $i < $num_rows1; $i++) {
			$tab_id = $adb->query_result($result1, $i, 'tabid');
			if (!in_array($tab_id, $temp_tabid)) {
				$temp_tabid[] = $tab_id;
				$access = [];
			}

			$action_id = $adb->query_result($result1, $i, 'operation');
			$per_id = $adb->query_result($result1, $i, 'permissions');
			$access[$action_id] = $per_id;
			$check[$tab_id] = $access;
		}

		\App\Log\Log::trace("Exiting getProfileActionPermission method ...");
		return $check;
	}

	/**
	 * Function to get all the vtiger_tab utility action permission for the specified vtiger_profile
	 * @param int $profileid Profile Id
	 * @return array Tab Utility Action Permission Array in the following format:
	 * $tabPermission = Array($tabid1=>Array(actionid1=>permission, actionid2=>permission,...,actionidn=>permission),
	 *                        $tabid2=>Array(actionid1=>permission, actionid2=>permission,...,actionidn=>permission),
	 *                                |
	 *                        $tabidn=>Array(actionid1=>permission, actionid2=>permission,...,actionidn=>permission))
	 */
	public static function getTabsUtilityActionPermission($profileid)
	{
		\App\Log\Log::trace("Entering getTabsUtilityActionPermission(" . $profileid . ") method ...");

		$adb = \App\Database\PearDatabase::getInstance();
		$check = [];
		$temp_tabid = [];
		$sql1 = "select * from vtiger_profile2utility where profileid=? order by(tabid)";
		$result1 = $adb->pquery($sql1, array($profileid));
		$num_rows1 = $adb->num_rows($result1);
		for ($i = 0; $i < $num_rows1; $i++) {
			$tab_id = $adb->query_result($result1, $i, 'tabid');
			if (!in_array($tab_id, $temp_tabid)) {
				$temp_tabid[] = $tab_id;
				$access = [];
			}

			$action_id = $adb->query_result($result1, $i, 'activityid');
			$per_id = $adb->query_result($result1, $i, 'permission');
			$access[$action_id] = $per_id;
			$check[$tab_id] = $access;
		}

		\App\Log\Log::trace("Exiting getTabsUtilityActionPermission method ...");
		return $check;
	}

	/**
	 * Function to get the Standard and Utility Profile Action Permissions for the specified vtiger_profileid
	 * @param int $profileid Profile Id
	 * @return array Profile Tabs Action Permission Array in the following format:
	 *    $tabActionPermission = Array($tabid1=>Array(actionid1=>permission, actionid2=>permission,...,actionidn=>permission),
	 *                        $tabid2=>Array(actionid1=>permission, actionid2=>permission,...,actionidn=>permission),
	 *                                |
	 *                        $tabidn=>Array(actionid1=>permission, actionid2=>permission,...,actionidn=>permission))
	 */
	public static function getProfileAllActionPermission($profileid)
	{
		\App\Log\Log::trace("Entering getProfileAllActionPermission(" . $profileid . ") method ...");
		$adb = \App\Database\PearDatabase::getInstance();
		$actionArr = self::getProfileActionPermission($profileid);
		$utilArr = self::getTabsUtilityActionPermission($profileid);
		foreach ($utilArr as $tabid => $act_arr) {
			$act_tab_arr = $actionArr[$tabid] ?? [];
			foreach ($act_arr as $utilid => $util_perr) {
				$act_tab_arr[$utilid] = $util_perr;
			}
			$actionArr[$tabid] = $act_tab_arr;
		}
		\App\Log\Log::trace("Exiting getProfileAllActionPermission method ...");
		return $actionArr;
	}
}
