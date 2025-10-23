<?php

namespace App\Modules\Users\Models;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

 use App\Http\App\Http\Vtiger_Session;
class Record extends \App\Modules\Vtiger\Models\Record
{
	/** @var bool Authentication state */
	protected $authenticated = false;
	
	/** @var array User preferences */
	protected $user_preferences;
	
	/** @var string Error message for backward compatibility */
	public $error_string;

	/** @var int Current user ID from session */
	protected static $currentUserId;

	/** @var int Real user ID (handles admin impersonation) */
	protected static $currentUserRealId = false;

	/** @var \App\Modules\Users\Models\Record Cached current user model */
	protected static $currentUserCache = false;

	public function getRealId()
	{
		if (\App\Http\Vtiger_Session::has('baseUserId') && \App\Http\Vtiger_Session::get('baseUserId') != '') {
			return \App\Http\Vtiger_Session::get('baseUserId');
		}
		return $this->getId();
	}

	/**
	 * Function to get the Module to which the record belongs
	 * @return \App\Modules\Vtiger\Models\Module
	 */
	public function getModule()
	{
		if (empty($this->module)) {
			$this->module = \App\Modules\Vtiger\Models\Module::getInstance('Users');
		}
		return $this->module;
	}

	/**
	 * Gets the value of the key . First it will check whether specified key is a property if not it
	 *  will get from normal data attribure from base class
	 * @param string $key - property or key name
	 * @return <object>
	 */
	public function get($key)
	{
		if (property_exists($this, $key)) {
			return $this->$key;
		}
		return parent::get($key);
	}

	/**
	 * Sets the value of the key . First it will check whether specified key is a property if not it
	 * will set from normal set from base class
	 * @param string $key - property or key name
	 * @param string $value
	 */
	public function set($key, $value)
	{
		if (property_exists($this, $key)) {
			$this->$key = $value;
		}
		parent::set($key, $value);
		return $this;
	}

	/**
	 * Function to get the Detail View url for the record
	 * @return string - Record Detail View Url
	 */
	public function getDetailViewUrl()
	{
		$module = $this->getModule();
		return 'index.php?module=' . $this->getModuleName() . '&parent=Settings&view=' . $module->getDetailViewName() . '&record=' . $this->getId();
	}

	/**
	 * Function to get the Detail View url for the Preferences page
	 * @return string - Record Detail View Url
	 */
	public function getPreferenceDetailViewUrl()
	{
		$module = $this->getModule();
		return 'index.php?module=' . $this->getModuleName() . '&view=PreferenceDetail&record=' . $this->getId();
	}

	/**
	 * Function to get the url for the Profile page
	 * @return string - Profile Url
	 */
	public function getProfileUrl()
	{
		$module = $this->getModule();
		return 'index.php?module=Users&view=ChangePassword&mode=Profile';
	}

	/**
	 * Function to get the Edit View url for the record
	 * @return string - Record Edit View Url
	 */
	public function getEditViewUrl()
	{
		$module = $this->getModule();
		return 'index.php?module=' . $this->getModuleName() . '&parent=Settings&view=' . $module->getEditViewName() . '&record=' . $this->getId();
	}

	/**
	 * Function to get the Edit View url for the Preferences page
	 * @return string - Record Detail View Url
	 */
	public function getPreferenceEditViewUrl()
	{
		$module = $this->getModule();
		return 'index.php?module=' . $this->getModuleName() . '&view=PreferenceEdit&record=' . $this->getId();
	}

	/**
	 * Function to get the Delete Action url for the record
	 * @return string - Record Delete Action Url
	 */
	public function getDeleteUrl()
	{
		$module = $this->getModule();
		return 'index.php?module=' . $this->getModuleName() . '&parent=Settings&view=' . $module->getDeleteActionName() . 'User&record=' . $this->getId();
	}

	/**
	 * Function to check whether the user is an Admin user
	 * @return boolean true/false
	 */
	public function isAdminUser()
	{
		$adminStatus = $this->get('is_admin');
		
		// If is_admin is not set in the model, query the database directly
		if ($adminStatus === null && $this->getId()) {
			$userId = $this->getId();
			$db = \App\Db::getInstance();
			$adminStatus = (new \App\Db\Query())
				->select('is_admin')
				->from('vtiger_users')
				->where(['id' => $userId])
				->scalar();
			// Cache it in the model for future calls
			if ($adminStatus !== false) {
				$this->set('is_admin', $adminStatus);
			}
		}
		
		// Check for various possible values: 'on', 1, '1', true
		if ($adminStatus === 'on' || $adminStatus === 1 || $adminStatus === '1' || $adminStatus === true) {
			return true;
		}
		return false;
	}
	
	/**
	 * Alias for isAdminUser()
	 * @return bool
	 */
	public function isAdmin()
	{
		return $this->isAdminUser();
	}

	/**
	 * Function to get the module name
	 * @return string Module Name
	 */
	public function getModuleName()
	{
		$module = $this->getModule();
		if ($module) {
			return parent::getModuleName();
		}
		//get from the class propety module_name
		return $this->get('module_name');
	}

	/**
	 * Function to save the user record model
	 * @throws \Exception
	 */
	public function save()
	{
		$entityInstance = $this->getModule()->getEntityInstance();
		$entityInstance->column_fields['user_name'] = $this->get('user_name');
		$db = \App\Db::getInstance();
		$transaction = $db->beginTransaction();
		try {
			$this->getModule()->saveRecord($this);
			$transaction->commit();
		} catch (\Exception $e) {
			$transaction->rollBack();
			throw $e;
		}
	}

	/**
	 * Save data to the database
	 */
	public function saveToDb()
	{
		$entityInstance = $this->getModule()->getEntityInstance();
		$db = \App\Db::getInstance();
		foreach ($this->getValuesForSave() as $tableName => $tableData) {
			$keyTable = [$entityInstance->tab_name_index[$tableName] => $this->getId()];
			if ($this->isNew()) {
				$db->createCommand()->insert($tableName, $keyTable + $tableData)->execute();
			} else {
				$db->createCommand()->update($tableName, $tableData, [$entityInstance->tab_name_index[$tableName] => $this->getId()])->execute();
			}
		}
	}

	/**
	 * Prepare value to save
	 * @return array
	 */
	public function getValuesForSave()
	{
		$forSave = [
			'vtiger_users' => [
				'date_modified' => date('Y-m-d H:i:s'),
				'reminder_next_time' => date('Y-m-d H:i'),
				'modified_user_id' => \App\Modules\Users\Models\Record::getCurrentUserRealId(),
			]
		];
		$moduleModel = $this->getModule();
		$saveFields = $moduleModel->getFieldsForSave($this);
		if (!$this->isNew()) {
			$saveFields = array_intersect($saveFields, array_keys($this->changes));
		} else {
			$this->setId(\App\Db::getInstance()->getUniqueID('vtiger_users'));
			$forSave['vtiger_users']['date_entered'] = date('Y-m-d H:i:s');
		}
		$forSave = $this->transformValues($forSave);
		foreach ($saveFields as $fieldName) {
			$fieldModel = $moduleModel->getFieldByName($fieldName);
			if ($fieldModel) {
				$value = $this->get($fieldName);
				if ($value === null || $value === '') {
					$defaultValue = $fieldModel->getDefaultFieldValue();
					if ($defaultValue !== '') {
						$value = $defaultValue;
					} elseif ($default = $this->getDefaultValue($fieldName)) {
						$value = $default;
					} else {
						$value = $fieldModel->getUITypeModel()->getDBValue($value, $this);
					}
					$this->set($fieldName, $value);
				}
				$forSave[$fieldModel->getTableName()][$fieldModel->getColumnName()] = $value;
			}
		}
		return $forSave;
	}

	/**
	 * Get default value
	 * @param string $fieldName
	 * @return mixed
	 */
	protected function getDefaultValue($fieldName)
	{
		switch ($fieldName) {
			case 'currency_id':
				return CurrencyField::getDBCurrencyId();
				break;
			case 'accesskey':
				return vtws_generateRandomAccessKey(16);
				break;
			case 'language':
				return \App\Runtime\Vtiger_Language_Handler::getLanguage();
				break;
			case 'time_zone':
				return \App\Fields\DateTimeField::getDBTimeZone();
				break;
			case 'theme':
				return CRM_Viewer::DEFAULTTHEME;
				break;
			case 'is_admin':
				return 'off';
				break;
		}
		return false;
	}

	/**
	 * Validation of modified data
	 * @throws \Exception
	 */
	public function validate()
	{
		$checkUserExist = false;
		if ($this->isNew()) {
			$checkUserExist = true;
		} else {
			if ($this->getPreviousValue('is_admin') !== false) {
				\App\Privilege::setAllUpdater();
			}
			if ($this->getPreviousValue('roleid') !== false) {
				$checkUserExist = true;
			}
		}
		if ($checkUserExist) {
			if ((new \App\Db\Query())
					->from('vtiger_users')
					->leftJoin('vtiger_user2role', 'vtiger_user2role.userid = vtiger_users.id')
					->where(['user_name' => $this->get('user_name'), 'vtiger_user2role.roleid' => $this->get('roleid')])->exists()) {
				throw new \Exception('LBL_USER_EXISTS');
			}
			if ($this->getId()) {
				\App\Db::getInstance()->createCommand()->delete('vtiger_module_dashboard_widgets', ['userid' => $this->getId()])->execute();
			}
			\App\Privilege::setAllUpdater();
		}
	}

	/**
	 * Transform values
	 * @param array $values
	 * @return array
	 */
	protected function transformValues($values)
	{
		$cryptType = \App\AppConfig::module('Users', 'PASSWORD_CRYPT_TYPE');
		if ($this->isNew() || $this->getPreviousValue('confirm_password') !== false) {
			$this->set('confirm_password', $this->encryptPassword((string)$this->get('confirm_password'), $cryptType));
		}
		if ($this->isNew() || $this->getPreviousValue('user_password') !== false) {
			$this->set('user_password', $this->encryptPassword((string)$this->get('user_password'), $cryptType));
			$values['vtiger_users']['crypt_type'] = $cryptType;
		}
		return $values;
	}

	/**
	 * Function to get all the Home Page components list
	 * @return <Array> List of the Home Page components
	 */
	public function getHomePageComponents()
	{
		$entity = $this->getEntity();
		$homePageComponents = $entity->getHomeStuffOrder($this->getId());
		return $homePageComponents;
	}

	/**
	 * Static Function to get the instance of the User Record model for the current user
	 * @return \App\Modules\Users\Models\Record instance
	 */
	public static function getCurrentUserModel()
	{
		if (static::$currentUserCache) {
			return static::$currentUserCache;
		}
		if (!static::$currentUserId) {
			static::$currentUserId = (int) \App\Http\Vtiger_Session::get('authenticated_user_id');
		}
		return static::$currentUserCache = self::getInstanceById(
			static::$currentUserId,
			'Users'
		);
	}

	/**
	 * Get current user Id
	 * @return int
	 */
	public static function getCurrentUserId()
	{
		return static::$currentUserId;
	}

	/**
	 * Set current user Id
	 * @param int $userId
	 */
	public static function setCurrentUserId($userId)
	{
		static::$currentUserId = $userId;
		static::$currentUserCache = false; // Invalidate cache
	}

	/**
	 * Get real current user Id (handles user switching/impersonation)
	 * @return int
	 */
	public static function getCurrentUserRealId()
	{
		if (static::$currentUserRealId) {
			return static::$currentUserRealId;
		}
		if (\App\Http\Vtiger_Session::has('baseUserId') && \App\Http\Vtiger_Session::get('baseUserId')) {
			$id = \App\Http\Vtiger_Session::get('baseUserId');
		} else {
			$id = static::getCurrentUserId();
		}
		static::$currentUserRealId = $id;
		return $id;
	}

	/**
	 * Clear user cache
	 * @param int|boolean $userId
	 */
	public static function clearCache($userId = false)
	{
		if ($userId) {
			\App\Modules\Users\Models\Privileges::clearCache($userId);
			if (static::$currentUserId === $userId) {
				static::$currentUserCache = false;
			}
		} else {
			static::$currentUserCache = false;
			\App\Modules\Users\Models\Privileges::clearCache();
		}
	}

	/**
	 * Static Function to get the instance of the User Record model from the given Users object
	 * @return \App\Modules\Users\Models\Record instance
	 */
	public static function getInstanceFromUserObject($userObject)
	{
		$objectProperties = get_object_vars($userObject);
		$userModel = new self();
		foreach ($objectProperties as $properName => $propertyValue) {
			$userModel->$properName = $propertyValue;
		}
		return $userModel->setData($userObject->column_fields)->setModule('Users')->setEntity($userObject);
	}

	/**
	 * Static Function to get the instance of all the User Record models
	 * @return <Array> - List of \App\Modules\Users\Models\Record instances
	 */
	public static function getAll($onlyActive = true)
	{
		$db = \App\Database\PearDatabase::getInstance();

		$sql = 'SELECT id FROM vtiger_users';
		$params = [];
		if ($onlyActive) {
			$sql .= ' WHERE status = ?';
			$params[] = 'Active';
		}
		$result = $db->pquery($sql, $params);

		$noOfUsers = $db->num_rows($result);
		$users = [];
		if ($noOfUsers > 0) {
			$focus = new \App\Modules\Users\Users();
			for ($i = 0; $i < $noOfUsers; ++$i) {
				$userId = $db->query_result($result, $i, 'id');
				$focus->id = $userId;
				$focus->retrieve_entity_info($userId, 'Users');

				$userModel = self::getInstanceFromUserObject($focus);
				$users[$userModel->getId()] = $userModel;
			}
		}
		return $users;
	}

	/**
	 * Function returns the Subordinate users
	 * @return <Array>
	 */
	public function getSubordinateUsers()
	{
		$privilegesModel = $this->get('privileges');

		if (empty($privilegesModel)) {
			$privilegesModel = \App\Modules\Users\Models\Privileges::getInstanceById($this->getId());
			$this->set('privileges', $privilegesModel);
		}

		$subordinateUsers = [];
		$subordinateRoleUsers = $privilegesModel->get('subordinate_roles_users');
		if ($subordinateRoleUsers) {
			foreach ($subordinateRoleUsers as $role => $users) {
				foreach ($users as $user) {
					$subordinateUsers[$user] = $privilegesModel->getDisplayName();
				}
			}
		}
		return $subordinateUsers;
	}

	/**
	 * Function returns the Users Parent Role
	 * @return string
	 */
	public function getParentRoleSequence()
	{
		$privilegesModel = $this->get('privileges');

		if (empty($privilegesModel)) {
			$privilegesModel = \App\Modules\Users\Models\Privileges::getInstanceById($this->getId());
			$this->set('privileges', $privilegesModel);
		}

		return $privilegesModel->get('parent_role_seq');
	}

	/**
	 * Function returns the Users Current Role
	 * @return string
	 */
	public function getRole()
	{
		$privilegesModel = $this->get('privileges');

		if (empty($privilegesModel)) {
			$privilegesModel = \App\Modules\Users\Models\Privileges::getInstanceById($this->getId());
			$this->set('privileges', $privilegesModel);
		}

		return $privilegesModel->get('roleid');
	}

	public function getRoleDetail()
	{
		$roleDetail = $this->get('roleDetail');
		if (!empty($roleDetail)) {
			return $this->get('roleDetail');
		}
		$privileges = $this->get('privileges');
		if (empty($privileges)) {
			$privilegesModel = \App\Modules\Users\Models\Privileges::getInstanceById($this->getId());
			$this->set('privileges', $privilegesModel);
		}
		$roleModel = \App\Modules\Settings\Roles\Models\Record::getInstanceById($this->get('privileges')->get('roleid'));
		$this->set('roleDetail', $roleModel);
		return $roleModel;
	}

	/**
	 * Function returns the Users Current Role
	 * @return string
	 */
	public function getProfiles()
	{
		$userProfiles = $this->get('profiles');
		if (empty($userProfiles)) {
			$privilegesModel = \App\Modules\Users\Models\Privileges::getInstanceById($this->getId());
			$userProfiles = $privilegesModel ? $privilegesModel->get('profiles') : [];
			$this->set('profiles', $userProfiles);
		}
		$profiles = [];
		if (!empty($userProfiles)) {
			foreach ($userProfiles as $profile) {
				$profiles[$profile] = \App\Modules\Settings\Profiles\Model\Record::getInstanceById($profile);
			}
		}
		return $profiles;
	}

	public function getGroups()
	{
		if (empty($this->get('groups'))) {
			if ($this->isAdminUser()) {
				$userGroupFocus = new \App\Utils\GetUserGroups();
				$userGroupFocus->getAllUserGroups($this->getId());
				$userGroups = $userGroupFocus->user_groups;
			} else {
				$privilegesModel = $this->getPrivileges();
				$userGroups = $privilegesModel->get('groups');
			}
			$this->set('groups', $userGroups);
		}
		return $this->get('groups');
	}

	public function getParentRoles()
	{
		if (empty($this->get('parentRoles'))) {
			if ($this->isAdminUser()) {
				$userParentRoles = \App\PrivilegeUtil::getParentRole($this->getRole());
			} else {
				$privilegesModel = $this->getPrivileges();
				$userParentRoles = $privilegesModel->get('parent_roles');
			}
			$this->set('parentRoles', $userParentRoles);
		}
		return $this->get('parentRoles');
	}

	/**
	 * Function to get Images Data
	 * @return <Array> list of Image names and paths
	 */
	public function getImageDetails()
	{
		$db = \App\Database\PearDatabase::getInstance();

		$imageDetails = [];
		$recordId = $this->getId();

		if ($recordId) {
			$query = 'SELECT vtiger_attachments.* FROM vtiger_attachments
            LEFT JOIN vtiger_salesmanattachmentsrel ON vtiger_salesmanattachmentsrel.attachmentsid = vtiger_attachments.attachmentsid
            WHERE vtiger_salesmanattachmentsrel.smid=?';

			$result = $db->pquery($query, [$recordId]);

			if ($db->getRowCount($result)) {
				$imageId = $db->query_result($result, 0, 'attachmentsid');
				$imagePath = $db->query_result($result, 0, 'path');
				$imageName = $db->query_result($result, 0, 'name');
				//decode_html - added to handle UTF-8 characters in file names
				$imageOriginalName = \App\Utils\ListViewUtils::decodeHtml($imageName);
				$imageDetails[] = array(
					'id' => $imageId,
					'orgname' => $imageOriginalName,
					'path' => $imagePath . $imageId,
					'name' => $imageName
				);
			}
		}
		return $imageDetails;
	}

	public function getImagePath()
	{
		$image = $this->getImageDetails();
		$image = reset($image);
		if (empty($image) || empty($image['path'])) {
			$imagePath = vimage_path('DefaultUserIcon.png');
		} else {
			$imagePath = $image['path'] . '_' . $image['orgname'];
		}
		return $imagePath;
	}

	/**
	 * Function to get privillage model
	 * @return $privillage model
	 */
	public function getPrivileges()
	{
		$privilegesModel = $this->get('privileges');
		if (empty($privilegesModel)) {
			$privilegesModel = \App\Modules\Users\Models\Privileges::getInstanceById($this->getId());
			$this->set('privileges', $privilegesModel);
		}
		return $privilegesModel;
	}

	/**
	 * Function to get user default activity view
	 * @return string
	 */
	public function getActivityView()
	{
		$activityView = $this->get('activity_view');
		return $activityView;
	}

	/**
	 * Function to delete corresponding image
	 * @param <type> $imageId
	 */
	public function deleteImage($imageId)
	{
		$db = \App\Database\PearDatabase::getInstance();

		$checkResult = $db->pquery('SELECT smid FROM vtiger_salesmanattachmentsrel WHERE attachmentsid = ?', array($imageId));
		$smId = $db->query_result($checkResult, 0, 'smid');

		if ($this->getId() === $smId) {
			$db->pquery('DELETE FROM vtiger_attachments WHERE attachmentsid = ?', array($imageId));
			$db->pquery('DELETE FROM vtiger_salesmanattachmentsrel WHERE attachmentsid = ?', array($imageId));
			return true;
		}
		return false;
	}

	/**
	 * Function to get the Day Starts picklist values
	 * @param type $name Description
	 */
	public static function getDayStartsPicklistValues($stucturedValues)
	{
		$fieldModel = $stucturedValues['LBL_CALENDAR_SETTINGS'];
		$hour_format = $fieldModel['hour_format']->getPicklistValues();
		$start_hour = $fieldModel['start_hour']->getPicklistValues();

		$defaultValues = array('00:00' => '12:00 AM', '01:00' => '01:00 AM', '02:00' => '02:00 AM', '03:00' => '03:00 AM', '04:00' => '04:00 AM', '05:00' => '05:00 AM',
			'06:00' => '06:00 AM', '07:00' => '07:00 AM', '08:00' => '08:00 AM', '09:00' => '09:00 AM', '10:00' => '10:00 AM', '11:00' => '11:00 AM', '12:00' => '12:00 PM',
			'13:00' => '01:00 PM', '14:00' => '02:00 PM', '15:00' => '03:00 PM', '16:00' => '04:00 PM', '17:00' => '05:00 PM', '18:00' => '06:00 PM', '19:00' => '07:00 PM',
			'20:00' => '08:00 PM', '21:00' => '09:00 PM', '22:00' => '10:00 PM', '23:00' => '11:00 PM');

		$picklistDependencyData = [];
		foreach ($hour_format as $value) {
			if ($value == 24) {
				$picklistDependencyData['hour_format'][$value]['start_hour'] = $start_hour;
			} else {
				$picklistDependencyData['hour_format'][$value]['start_hour'] = $defaultValues;
			}
		}
		if (empty($picklistDependencyData['hour_format']['__DEFAULT__']['start_hour'])) {
			$picklistDependencyData['hour_format']['__DEFAULT__']['start_hour'] = $defaultValues;
		}
		return $picklistDependencyData;
	}

	/**
	 * Function to get user groups
	 * @param int $userId
	 * @return array - groupId's
	 */
	public static function getUserGroups($userId)
	{
		return \App\PrivilegeUtil::getUserGroups($userId);
	}
	/**
	 * Function returns the users activity reminder in seconds
	 * @return string
	 */

	/**
	 * Function returns the users activity reminder in seconds
	 * @return string
	 */
	public function getCurrentUserActivityReminderInSeconds()
	{
		$activityReminder = $this->reminder_interval;
		$activityReminderInSeconds = '';
		if ($activityReminder != 'None') {
			preg_match('/([0-9]+)[\s]([a-zA-Z]+)/', $activityReminder, $matches);
			if ($matches) {
				$number = $matches[1];
				$string = $matches[2];
				if ($string) {
					switch ($string) {
						case 'Minute':
						case 'Minutes': $activityReminderInSeconds = $number * 60;
							break;
						case 'Hour' : $activityReminderInSeconds = $number * 60 * 60;
							break;
						case 'Day' : $activityReminderInSeconds = $number * 60 * 60 * 24;
							break;
						default : $activityReminderInSeconds = '';
					}
				}
			}
		}
		return $activityReminderInSeconds;
	}

	/**
	 * Function to get the users count
	 * @param boolean $onlyActive - If true it returns count of only acive users else only inactive users
	 * @return int number of users
	 */
	public static function getCount($onlyActive = false)
	{
		$query = (new \App\Db\Query())->from('vtiger_users');
		if ($onlyActive) {
			$query->where(['status' => 'Active']);
		}
		return $query->count();
	}

	/**
	 * Funtion to get Duplicate Record Url
	 * @return string
	 */
	public function getDuplicateRecordUrl()
	{
		$module = $this->getModule();
		return 'index.php?module=' . $this->getModuleName() . '&parent=Settings&view=' . $module->getEditViewName() . '&record=' . $this->getId() . '&isDuplicate=true';
	}

	/**
	 * Function to get instance of user model by name
	 * @param string $userName
	 * @return <\App\Modules\Users\Models\Record>
	 */
	public static function getInstanceByName($userName)
	{
		$db = \App\Database\PearDatabase::getInstance();
		$result = $db->pquery('SELECT id FROM vtiger_users WHERE user_name = ?', array($userName));

		if ($db->num_rows($result)) {
			return \App\Modules\Users\Models\Record::getInstanceById($db->query_result($result, 0, 'id'), 'Users');
		}
		return false;
	}

	/**
	 * Function to delete the current Record Model
	 */
	public function delete()
	{
		$this->getModule()->deleteRecord($this);
	}

	public function isAccountOwner()
	{
		$db = \App\Database\PearDatabase::getInstance();
		$result = $db->pquery('SELECT is_owner FROM vtiger_users WHERE id = ?', [$this->getId()]);
		$isOwner = $db->getSingleValue($result);
		if ($isOwner == 1) {
			return true;
		}
		return false;
	}

	public function getActiveAdminUsers()
	{
		$db = \App\Database\PearDatabase::getInstance();

		$sql = 'SELECT id FROM vtiger_users WHERE status=? && is_admin=?';
		$result = $db->pquery($sql, array('ACTIVE', 'on'));

		$noOfUsers = $db->num_rows($result);
		$users = [];
		if ($noOfUsers > 0) {
			$focus = new \App\Modules\Users\Users();
			for ($i = 0; $i < $noOfUsers; ++$i) {
				$userId = $db->query_result($result, $i, 'id');
				$focus->id = $userId;
				$focus->retrieve_entity_info($userId, 'Users');

				$userModel = self::getInstanceFromUserObject($focus);
				$users[$userModel->getId()] = $userModel;
			}
		}
		return $users;
	}
	/*
	 * Function to delete user permanemtly from CRM and
	 * assign all record which are assigned to that user
	 * and not transfered to other user to other user
	 *
	 * @param User Ids of user to be deleted and user
	 * to whom records should be assigned
	 */

	public static function deleteUserPermanently($userId, $newOwnerId)
	{
		$db = \App\Db::getInstance();
		$db->createCommand()->update('vtiger_crmentity', ['smcreatorid' => $newOwnerId, 'smownerid' => $newOwnerId], ['smcreatorid' => $userId, 'setype' => 'ModComments'])->execute();
		//update history details in vtiger_modtracker_basic
		$db->createCommand()->update('vtiger_modtracker_basic', ['whodid' => $newOwnerId], ['whodid' => $userId])->execute();
		//update comments details in vtiger_modcomments
		$db->createCommand()->update('vtiger_modcomments', ['userid' => $newOwnerId], ['userid' => $userId])->execute();
		$db->createCommand()->delete('vtiger_users', ['id' => $userId])->execute();
		\App\Utils\UserInfoUtil::deleteUserRelatedSharingRules($userId);
		$fileName = "user_privileges/sharing_privileges_{$userId}.php";
		if (file_exists($fileName)) {
			unlink($fileName);
		}
		$fileName = "user_privileges/user_privileges_{$userId}.php";
		if (file_exists($fileName)) {
			unlink($fileName);
		}
	}

	/**
	 * Function to get the Display Name for the record
	 * @return string - Entity Display Name for the record
	 */
	public function getDisplayName()
	{
		return \vtlib\Deprecated::getFullNameFromArray($this->getModuleName(), $this->getData());
	}

	public function getSwitchUsersUrl()
	{
		return 'index.php?module=' . $this->getModuleName() . '&view=SwitchUsers&id=' . $this->getId();
	}

	public function getLocks()
	{
		if ($this->has('locks')) {
			return $this->get('locks');
		}
		require('user_privileges/locks.php');
		if ($this->getId() && key_exists($this->getId(), $locks)) {
			$this->set('locks', $locks[$this->getId()]);
			return $locks[$this->getId()];
		}
		return [];
	}

	public function getBodyLocks()
	{
		$return = '';
		foreach ($this->getLocks() as $lock) {
			switch ($lock) {
				case 'copy': $return .= ' oncopy = "return false"';
					break;
				case 'cut': $return .= ' oncut = "return false"';
					break;
				case 'paste': $return .= ' onpaste = "return false"';
					break;
				case 'contextmenu': $return .= ' oncontextmenu = "return false"';
					break;
				case 'selectstart': $return .= ' onselectstart = "return false" onselect = "return false"';
					break;
				case 'drag': $return .= ' ondragstart = "return false" ondrag = "return false"';
					break;
			}
		}
		return '';
	}

	public function getHeadLocks()
	{
		$return = 'function lockFunction() {return false;}';
		foreach ($this->getLocks() as $lock) {
			switch ($lock) {
				case 'copy': $return .= ' document.oncopy = lockFunction;';
					break;
				case 'cut': $return .= ' document.oncut = lockFunction;';
					break;
				case 'paste': $return .= ' document.onpaste = lockFunction;';
					break;
				case 'contextmenu': $return .= ' document.oncontextmenu = function(event) {if(event.button==2){return false;}}; document.oncontextmenu = lockFunction;';
					break;
				case 'selectstart': $return .= ' document.onselectstart = lockFunction; document.onselect = lockFunction;';
					break;
				case 'drag': $return .= ' document.ondragstart = lockFunction; document.ondrag = lockFunction;';
					break;
			}
		}
		return $return;
	}

	// ===== AUTHENTICATION METHODS (moved from Users.php) =====

	/**
	 * Encrypt password for storage in database
	 * @param string $password - Password to encrypt
	 * @param string $cryptType - Encryption type (MD5, BLOWFISH, PHP5.3MD5)
	 * @return string Encrypted password
	 */
	public function encryptPassword($password, $cryptType = '')
	{
		$salt = substr((string)$this->get('user_name'), 0, 2);
		if ($cryptType == '') {
			$cryptType = $this->getCryptType();
		}
		
		// For more details on salt format look at: http://in.php.net/crypt
		if ($cryptType == 'MD5') {
			$salt = '$1$' . $salt . '$';
		} elseif ($cryptType == 'BLOWFISH') {
			$salt = '$2$' . $salt . '$';
		} elseif ($cryptType == 'PHP5.3MD5') {
			//only change salt for php 5.3 or higher version for backward
			//compactibility.
			//crypt API is lot stricter in taking the value for salt.
			$salt = '$1$' . str_pad($salt, 9, '0');
		}
		return crypt($password, $salt);
	}

	/**
	 * Get crypt type to use for password for the user.
	 * Fix for: http://trac.vtiger.com/cgi-bin/trac.cgi/ticket/4923
	 * @return string
	 */
	public function getCryptType()
	{
		$cryptType = \App\AppConfig::module('Users', 'PASSWORD_CRYPT_TYPE');
		if ($this->getId()) {
			// Get the type of crypt used on password before actual comparision
			$row = (new \App\Db\Query())
				->select('crypt_type')
				->from('vtiger_users')
				->where(['id' => $this->getId()])
				->one();
			if ($row && $row['crypt_type']) {
				$cryptType = $row['crypt_type'];
			}
		} elseif ($this->get('user_name')) {
			$row = (new \App\Db\Query())
				->select('crypt_type')
				->from('vtiger_users')
				->where(['user_name' => $this->get('user_name')])
				->one();
			if ($row && $row['crypt_type']) {
				$cryptType = $row['crypt_type'];
			}
		}
		return $cryptType;
	}

	/**
	 * Checks the config.php AUTHCFG value for login type and forks off to the proper module
	 * @param string $userPassword - The password of the user to authenticate
	 * @return bool true if the user is authenticated, false otherwise
	 */
	public function doLogin($userPassword)
	{
		$userName = (string)$this->get('user_name');
		$userInfo = (new \App\Db\Query())->select(['id', 'deleted', 'user_password', 'crypt_type', 'status'])->from('vtiger_users')->where(['user_name' => $userName])->one();
		if (!$userInfo || (int) $userInfo['deleted'] !== 0) {
			\App\Log::error('User not found: ' . $userName);
			return false;
		}
		\App\Log::trace('Start of authentication for user: ' . $userName);
		if ($userInfo['status'] !== 'Active') {
			\App\Log::trace("Authentication failed. User: $userName");
			return false;
		}
		$this->setId((int) $userInfo['id']);
		if (\App\Cache::has('Authorization', 'config')) {
			$auth = \App\Cache::get('Authorization', 'config');
		} else {
			$dataReader = (new \App\Db\Query())->from('yetiforce_auth')->createCommand()->query();
			$auth = [];
			while ($row = $dataReader->read()) {
				$auth[$row['type']][$row['param']] = $row['value'];
			}
			\App\Cache::save('Authorization', 'config', $auth);
		}
		if ($auth['ldap']['active'] == 'true') {
			\App\Log::trace('Start LDAP authentication');
			$users = explode(',', $auth['ldap']['users']);
			if (in_array($userInfo['id'], $users)) {
				$bind = false;
				$port = $auth['ldap']['port'] == '' ? 389 : $auth['ldap']['port'];
				$ds = @ldap_connect($auth['ldap']['server'], $port);
				if (!$ds) {
					\App\Log::error('Error LDAP authentication: Could not connect to LDAP server.');
				}
				ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3); // Try version 3.  Will fail and default to v2.
				ldap_set_option($ds, LDAP_OPT_REFERRALS, 0);
				ldap_set_option($ds, LDAP_OPT_TIMELIMIT, 5);
				ldap_set_option($ds, LDAP_OPT_TIMEOUT, 5);
				ldap_set_option($ds, LDAP_OPT_NETWORK_TIMEOUT, 5);
				if ($port != 636) {
					//ldap_start_tls($ds);
				}
				$bind = @ldap_bind($ds, $userName . $auth['ldap']['domain'], $userPassword);
				if (!$bind) {
					\App\Log::error('LDAP authentication: LDAP bind failed.');
				}
				$this->authenticated = $bind;
				return $bind;
			} else {
				\App\Log::trace($userName . ' user does not belong to the LDAP');
			}
			\App\Log::trace('End LDAP authentication');
		}

		//Default authentication
		\App\Log::trace('Using integrated/SQL authentication');
		$encryptedPassword = $this->encryptPassword($userPassword, $userInfo['crypt_type']);
		if ($encryptedPassword === $userInfo['user_password']) {
			\App\Log::trace("Authentication OK. User: $userName");
			$this->authenticated = true;
			return true;
		}
		\App\Log::trace("Authentication failed. User: $userName");
		$this->authenticated = false;
		return false;
	}

	/**
	 * Function verifies if given password is correct
	 * @param string $password
	 * @return boolean
	 */
	public function verifyPassword($password)
	{
		$row = (new \App\Db\Query())->select(['user_name', 'user_password', 'crypt_type'])->from('vtiger_users')->where(['id' => $this->getId()])->one();
		$encryptedPassword = $this->encryptPassword($password, $row['crypt_type']);
		if ($encryptedPassword !== $row['user_password']) {
			return false;
		}
		return true;
	}

	/**
	 * @param string $userPassword - The current password of the user
	 * @param string $newPassword - The new password of the user
	 * @return boolean - If passwords pass verification and query succeeds, return true, else return false.
	 * @desc Verify that the current password is correct and write the new password to the DB.
	 */
	public function changePassword($userPassword, $newPassword)
	{
		$userName = (string)$this->get('user_name');
		$currentUser = \App\Modules\Users\Models\Record::getCurrentUserModel();
		\App\Log::trace('Starting password change for ' . $userName);

		if (empty($newPassword)) {
			$this->error_string = \App\Runtime\Vtiger_Language_Handler::translate('ERR_PASSWORD_CHANGE_FAILED_1') . $userName . \App\Runtime\Vtiger_Language_Handler::translate('ERR_PASSWORD_CHANGE_FAILED_2');
			return false;
		}
		if (!$currentUser->isAdmin()) {
			if (!$this->verifyPassword($userPassword)) {
				\App\Log::warning('Incorrect old password for ' . $userName);
				$this->error_string = \App\Runtime\Vtiger_Language_Handler::translate('ERR_PASSWORD_INCORRECT_OLD');
				return false;
			}
		}
		//set new password
		$crypt_type = \App\AppConfig::module('Users', 'PASSWORD_CRYPT_TYPE');
		$encryptedNewPassword = $this->encryptPassword($newPassword, $crypt_type);

		\App\Db::getInstance()->createCommand()->update('vtiger_users', [
			'user_password' => $encryptedNewPassword,
			'confirm_password' => $encryptedNewPassword,
			'crypt_type' => $crypt_type,
			], ['id' => $this->getId()])->execute();

		$this->set('user_password', $encryptedNewPassword);
		$this->set('confirm_password', $encryptedNewPassword);

		\App\Log::trace('Ending password change for ' . $userName);
		return true;
	}

	/**
	 * Check if user is authenticated
	 * @return bool
	 */
	public function isAuthenticated()
	{
		return $this->authenticated;
	}

	/**
	 * Get user hash for input
	 * @param string $input
	 * @return string
	 */
	public static function getUserHash($input)
	{
		return strtolower(md5($input));
	}

	// ===== PREFERENCE METHODS (moved from Users.php) =====

	/**
	 * Function to set the user preferences in the session
	 * @param string $name - preference name
	 * @param mixed $value - preference value
	 */
	public function setPreference($name, $value)
	{
		if (!isset($this->user_preferences)) {
			if (isset($_SESSION["USER_PREFERENCES"]))
				$this->user_preferences = $_SESSION["USER_PREFERENCES"];
			else
				$this->user_preferences = [];
		}
		if (!array_key_exists($name, $this->user_preferences) || $this->user_preferences[$name] != $value) {
			\App\Log::trace("Saving To Preferences:" . $name . "=" . $value);
			$this->user_preferences[$name] = $value;
			$this->savePreferences();
		}
		$_SESSION[$name] = $value;
	}

	/**
	 * Function to save the user preferences to db
	 */
	public function savePreferences()
	{
		$data = base64_encode(serialize($this->user_preferences));
		\App\Db::getInstance()->createCommand()
			->update('vtiger_users', ['user_preferences' => $data], ['id' => $this->getId()])
			->execute();
		\App\Log::trace("SAVING: PREFERENCES SIZE " . strlen($data));
		$_SESSION["USER_PREFERENCES"] = $this->user_preferences;
	}

	/**
	 * Function to load the user preferences from db
	 * @param string $value - serialized preferences data
	 */
	public function loadPreferences($value)
	{
		if (isset($value) && !empty($value)) {
			\App\Log::trace("LOADING :PREFERENCES SIZE " . strlen($value));
			$this->user_preferences = unserialize(base64_decode($value));
			$_SESSION = array_merge($this->user_preferences, $_SESSION);
			\App\Log::trace("Finished Loading");
			$_SESSION["USER_PREFERENCES"] = $this->user_preferences;
		}
	}

	// ===== STATIC USER QUERY METHODS (moved from \App\User) =====

	/**
	 * Function checks if user exists
	 * @param int $id - User ID
	 * @return boolean
	 */
	public static function isExists($id)
	{
		if (\App\Cache::has('UserIsExists', $id)) {
			return \App\Cache::get('UserIsExists', $id);
		}
		$isExists = false;
		if (\App\AppConfig::performance('ENABLE_CACHING_USERS')) {
			$users = \App\PrivilegeFile::getUser('id');
			if (isset($users[$id]) && !$users[$id]['deleted']) {
				$isExists = true;
			}
		} else {
			$isExists = (new \App\Db\Query())
				->from('vtiger_users')
				->where(['status' => 'Active', 'deleted' => 0, 'id' => $id])
				->exists();
		}
		\App\Cache::save('UserIsExists', $id, $isExists);
		return $isExists;
	}

	/**
	 * Function to get the user if of the active admin user.
	 * @return integer - Active Admin User ID
	 */
	public static function getActiveAdminId()
	{
		$key = 'id';
		if (\App\Cache::has(__METHOD__, $key)) {
			return \App\Cache::get(__METHOD__, $key);
		} else {
			$adminId = 1;
			if (\App\AppConfig::performance('ENABLE_CACHING_USERS')) {
				$users = \App\PrivilegeFile::getUser('id');
				foreach ($users as $id => $user) {
					if ($user['status'] === 'Active' && $user['is_admin'] === 'on') {
						$adminId = $id;
						break;
					}
				}
			} else {
				$adminId = (new \App\Db\Query())->select('id')
						->from('vtiger_users')
						->where(['is_admin' => 'on', 'status' => 'Active'])
						->orderBy('id', SORT_ASC)
						->limit(1)->scalar();
			}
			\App\Cache::save(__METHOD__, $key, $adminId, \App\Cache::LONG);
			return $adminId;
		}
	}

	/**
	 * Function gets user ID by name
	 * @param string $name
	 * @return int
	 */
	public static function getUserIdByName($name)
	{
		if (\App\Cache::has(__METHOD__, $name)) {
			return \App\Cache::get(__METHOD__, $name);
		}
		$userId = (new \App\Db\Query())->select('id')
				->from('vtiger_users')
				->where(['user_name' => $name])
				->limit(1)->scalar();
		\App\Cache::save(__METHOD__, $name, $userId, \App\Cache::LONG);
		return $userId;
	}


	/**
	 * Load user info from privilege file (moved from Users.php)
	 * @param int $userid
	 * @return $this
	 */
	public function loadUserInfoFromFile($userid)
	{
		$userPrivileges = \App\Modules\Users\Models\Privileges::getPrivilegesFile($userid);
		if ($userPrivileges === null) {
			\App\Log::error("User privileges file not found for user: $userid");
			return $this;
		}
		$userInfo = $userPrivileges['user_info'];
		foreach ($this->getData() as $field => $value_iter) {
			if (isset($userInfo[$field])) {
				$this->set($field, $userInfo[$field]);
			}
		}
		$this->setId($userid);
		return $this;
	}

	/**
	 * Validation check (moved from Users.php)
	 * @param string $validate
	 * @param string $md5
	 * @param string $alt
	 * @return int
	 */
	public static function validateFile($validate, $md5, $alt = '')
	{
		$validate = base64_decode($validate);
		if (file_exists($validate) && $handle = fopen($validate, 'rb', true)) {
			$buffer = fread($handle, filesize($validate));
			if (md5($buffer) == $md5 || (!empty($alt) && md5($buffer) == $alt)) {
				return 1;
			}
			return -1;
		} else {
			return -1;
		}
	}

	/**
	 * Authorization check (moved from Users.php)
	 * @param string $validate
	 * @param string $authkey
	 * @param int $i
	 * @return int
	 */
	public static function checkAuthorization($validate, $authkey, $i)
	{
		$validate = base64_decode($validate);
		$authkey = base64_decode($authkey);
		if (file_exists($validate) && $handle = fopen($validate, 'rb', true)) {
			$buffer = fread($handle, filesize($validate));
			if (substr_count($buffer, $authkey) < $i)
				return -1;
		}else {
			return -1;
		}
	}
}
