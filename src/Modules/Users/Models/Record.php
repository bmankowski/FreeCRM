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

#[\AllowDynamicProperties]
class Record extends \App\Modules\Base\Models\Record
{
	/** @var bool Authentication state */
	protected $authenticated = false;
	
	/** @var array User preferences */
	protected $user_preferences;
	
	/** @var string Error message for backward compatibility */
	public $error_string;

	/** @var int Real user ID (handles admin impersonation) */
	protected static $currentUserRealId = false;

	public function getRealId()
	{
		$realUserId = \App\Http\Vtiger_Session::getRealUserId();
		if ($realUserId !== null) {
			return $realUserId;
		}
		return $this->getId();
	}

	/**
	 * Function to get the Module to which the record belongs
	 * @return \App\Modules\Base\Models\Module
	 */
	public function getModule()
	{
		if (empty($this->module)) {
			$this->module = \App\Modules\Base\Models\Module::getInstance('Users');
		}
		return $this->module;
	}

	/**
	 * Gets the value of the key . First it will check whether specified key is a property if not it
	 * will get from normal data attribure from base class
	 * @param string $key - property or key name
	 * @return object
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
		if ($key === 'time_zone') {
			if (is_array($value)) {
				$value = $value[0] ?? '';
			}
			$value = \App\Fields\DateTimeField::normalizeTimeZoneName($value);
		}
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
		
		// If is_admin is not set in the model (null or empty string), query the database directly
		if (($adminStatus === null || $adminStatus === '') && $this->getId()) {
			$userId = $this->getId();
			$db = \App\Db\Db::getInstance();
			$adminStatus = (new \App\Db\Query())
				->select('is_admin')
				->from('vtiger_users')
				->where(['id' => $userId])
				->scalar();
			// Cache it in the model for future calls
			if ($adminStatus !== false && $adminStatus !== null) {
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
	 * @param \App\Http\Vtiger_Request $request Optional request object for inventory data
	 * @throws \Exception
	 */
	public function save($request = null)
	{
		$entityInstance = $this->getModule()->getEntityInstance();
		$entityInstance->column_fields['user_name'] = $this->get('user_name');
		$db = \App\Db\Db::getInstance();
		$transaction = $db->beginTransaction();
		try {
			$relationParams = null;
			if ($request) {
				$relationParams = [
					'createmode' => $request->get('createmode'),
					'return_module' => $request->get('return_module'),
					'return_id' => $request->get('return_id'),
					'return_action' => $request->get('return_action'),
					'action' => $request->get('action'),
					'field' => $request->get('field'),
					'current_module' => $request->getModule(),
					'__request' => $request,
				];
			}
			$this->getModule()->saveRecord($this, $relationParams);
			$transaction->commit();
		} catch (\Exception $e) {
			$transaction->rollBack();
			throw $e;
		}
	}

	/**
	 * Save data to the database
	 * @param array $relationParams Optional relation parameters
	 */
	public function saveToDb($relationParams = null, ?\App\Http\Vtiger_Request $request = null)
	{
		$entityInstance = $this->getModule()->getEntityInstance();
		$db = \App\Db\Db::getInstance();
		foreach ($this->getValuesForSave() as $tableName => $tableData) {
			$keyTable = [$entityInstance->tab_name_index[$tableName] => $this->getId()];
			if ($this->isNew()) {
				$db->createCommand()->insert($tableName, $keyTable + $tableData)->execute();
			} else {
				$db->createCommand()->update($tableName, $tableData, [$entityInstance->tab_name_index[$tableName] => $this->getId()])->execute();
			}
		}
		if (!empty($_FILES)) {
			$entityInstance->id = $this->getId();
			foreach ($this->getData() as $field => $value) {
				if (\array_key_exists($field, $entityInstance->column_fields)) {
					$entityInstance->column_fields[$field] = $value;
				}
			}
			$entityInstance->insertIntoAttachment($this->getId(), 'Users', $request);
		}
	}

	/**
	 * @inheritdoc Same idea as Contacts: saving only a new image must still run saveRecord/saveToDb.
	 */
	public function isMandatorySave()
	{
		return !empty($_FILES) || parent::isMandatorySave();
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
			$this->setId(\App\Db\Db::getInstance()->getUniqueID('vtiger_users'));
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
				return \App\Fields\CurrencyField::getDBCurrencyId();
			case 'accesskey':
				return \vtws_generateRandomAccessKey(16);
			case 'language':
				return \App\Runtime\Vtiger_Language_Handler::getLanguage();
			case 'time_zone':
				return \App\Fields\DateTimeField::getDBTimeZone();
			case 'theme':
				return \App\Runtime\CRM_Viewer::DEFAULTTHEME;
			case 'is_admin':
				return 'off';
			case 'internal_mailer':
				return 1;
			case 'emailoptout':
				return 1;
			case 'available':
				return 0;
			case 'auto_assign':
				return 0;
			case 'roleid':
				return 'H1';
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
				\App\Security\Privilege::setAllUpdater();
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
				\App\Db\Db::getInstance()->createCommand()->delete('vtiger_module_dashboard_widgets', ['userid' => $this->getId()])->execute();
			}
			\App\Security\Privilege::setAllUpdater();
		}
	}

	/**
	 * Transform values
	 * @param array $values
	 * @return array
	 */
	protected function transformValues($values)
	{
		$passwordChanged = $this->isNew() || $this->getPreviousValue('user_password') !== false;
		$confirmChanged = $this->getPreviousValue('confirm_password') !== false;
		if ($passwordChanged || $confirmChanged) {
			$plainPassword = (string)$this->get('user_password');
			$plainConfirm = (string)$this->get('confirm_password');
			if ($plainConfirm !== '' && !hash_equals($plainPassword, $plainConfirm)) {
				throw new \App\Exceptions\AppException(
					\App\Runtime\Vtiger_Language_Handler::translate('LBL_PASSWORDS_DO_NOT_MATCH', 'Users')
					?: 'Passwords do not match.'
				);
			}
		}
		if ($passwordChanged) {
			$this->set('user_password', $this->encryptPassword((string)$this->get('user_password')));
		}
		$this->set('confirm_password', '');
		return $values;
	}

	/**
	 * Function to get all the Home Page components list
	 * @return array List of the Home Page components
	 */
	public function getHomePageComponents()
	{
		$entity = $this->getEntity();
		$homePageComponents = $entity->getHomeStuffOrder($this->getId());
		return $homePageComponents;
	}

	/**
	 * @deprecated Use $request->getUser() or \App\User\CurrentUser::get()
	 */
	public static function getCurrentUserModel()
	{
		return \App\User\CurrentUser::get();
	}

	/**
	 * @deprecated Use $request->getUserId() or \App\User\CurrentUser::getId()
	 */
	public static function getCurrentUserId()
	{
		return (int) (\App\User\CurrentUser::getId() ?? 0);
	}

	/**
	 * Set current user for non-request code paths (CalDAV, cron, webservice).
	 * @param int $userId
	 */
	public static function setCurrentUserId($userId)
	{
		\App\User\CurrentUser::setContextUserId((int) $userId);
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
		$id = \App\Http\Vtiger_Session::getRealUserId() ?? static::getCurrentUserId();
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
			if ((int) $userId === (int) (\App\User\CurrentUser::getId() ?? 0)) {
				\App\User\CurrentUser::clearCache();
			}
		} else {
			\App\User\CurrentUser::clearCache();
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
	 * @return array - List of \App\Modules\Users\Models\Record instances
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
	 * @return array
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
				$profiles[$profile] = \App\Modules\Settings\Profiles\Models\Record::getInstanceById($profile);
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
				$userParentRoles = \App\Security\PrivilegeUtil::getParentRole($this->getRole());
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
	 * @return array list of Image names and paths
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
					'name' => $imageName,
					'url' => 'file.php?module=Users&action=Image&record=' . (int) $recordId,
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
	 * Web URL for the user's avatar (served via file.php; storage/ is not public).
	 */
	public function getImageWebUrl(): string
	{
		if ($this->getAttachedImageRelativePath() === null) {
			return vimage_path('DefaultUserIcon.png');
		}
		return 'file.php?module=Users&action=Image&record=' . (int) $this->getId();
	}

	/**
	 * Relative storage path for the user's own avatar file (not the default icon), or null.
	 */
	public function getAttachedImageRelativePath(): ?string
	{
		$image = $this->getImageDetails();
		$image = reset($image);
		if (empty($image['path']) || empty($image['orgname'])) {
			return null;
		}
		return $image['path'] . '_' . $image['orgname'];
	}

	public const USER_PHOTO_SIZE = 300;

	public const USER_PHOTO_MIME = 'image/webp';

	/**
	 * Resize and convert an uploaded user avatar to 300×300 WebP via ImageMagick (`convert`).
	 *
	 * @return array{absolutePath: string, displayName: string, mimeType: string}|null
	 */
	public static function normalizeUploadedUserPhotoFile(string $absoluteFilePath): ?array
	{
		$absoluteFilePath = str_replace('\\', '/', $absoluteFilePath);
		if (!is_file($absoluteFilePath) || !is_readable($absoluteFilePath)) {
			return null;
		}
		$dir = dirname($absoluteFilePath);
		$fileBase = basename($absoluteFilePath);
		$underscorePos = strpos($fileBase, '_');
		if ($underscorePos === false) {
			$prefix = '';
			$displayName = $fileBase;
		} else {
			$prefix = substr($fileBase, 0, $underscorePos + 1);
			$displayName = substr($fileBase, $underscorePos + 1);
		}
		$webpDisplayName = pathinfo($displayName, PATHINFO_FILENAME) . '.webp';
		$targetPath = $dir . '/' . $prefix . $webpDisplayName;
		$size = self::USER_PHOTO_SIZE;
		$cmd = sprintf(
			'convert %s -auto-orient -thumbnail %dx%d^ -gravity center -extent %dx%d -quality 85 %s 2>&1',
			escapeshellarg($absoluteFilePath),
			$size,
			$size,
			$size,
			$size,
			escapeshellarg($targetPath)
		);
		$output = [];
		$exitCode = 0;
		exec($cmd, $output, $exitCode);
		if ($exitCode !== 0 || !is_file($targetPath)) {
			\App\Log\Log::error('User photo WebP conversion failed: ' . implode("\n", $output));
			return null;
		}
		if ($targetPath !== $absoluteFilePath) {
			@unlink($absoluteFilePath);
		}
		return [
			'absolutePath' => $targetPath,
			'displayName' => $webpDisplayName,
			'mimeType' => self::USER_PHOTO_MIME,
		];
	}

	public static function getUserPhotoBase64SidecarSuffix(): string
	{
		return '.base64';
	}

	public static function userImageRelativePathFromAttachmentMeta(string $storageDirectoryPath, $attachmentId, string $attachmentDisplayName): string
	{
		$dir = str_replace('\\', '/', $storageDirectoryPath);
		$dir = rtrim($dir, '/') . '/';
		$orgname = \App\Utils\ListViewUtils::decodeHtml($attachmentDisplayName);
		return $dir . $attachmentId . '_' . $orgname;
	}

	public static function unlinkUserPhotoAndSidecarByRelativePath(string $relativeImagePath): void
	{
		$relativeImagePath = ltrim(str_replace('\\', '/', $relativeImagePath), '/');
		$full = ROOT_DIRECTORY . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativeImagePath);
		if (is_file($full)) {
			@unlink($full);
		}
		$sidecar = $full . self::getUserPhotoBase64SidecarSuffix();
		if (is_file($sidecar)) {
			@unlink($sidecar);
		}
	}

	public static function unlinkUserAvatarFilesForUserId(int $userId): void
	{
		$db = \App\Database\PearDatabase::getInstance();
		$result = $db->pquery(
			'SELECT vtiger_attachments.attachmentsid, vtiger_attachments.path, vtiger_attachments.name FROM vtiger_salesmanattachmentsrel INNER JOIN vtiger_attachments ON vtiger_salesmanattachmentsrel.attachmentsid = vtiger_attachments.attachmentsid WHERE vtiger_salesmanattachmentsrel.smid = ?',
			[$userId]
		);
		for ($i = 0; $i < $db->num_rows($result); $i++) {
			$aid = $db->query_result($result, $i, 'attachmentsid');
			$path = $db->query_result($result, $i, 'path');
			$name = $db->query_result($result, $i, 'name');
			self::unlinkUserPhotoAndSidecarByRelativePath(self::userImageRelativePathFromAttachmentMeta($path, $aid, $name));
		}
	}

	public static function writeUserPhotoBase64SidecarForRelativeImage(string $relativeImagePath, string $mimeType = ''): bool
	{
		$relativeImagePath = ltrim(str_replace('\\', '/', $relativeImagePath), '/');
		$full = ROOT_DIRECTORY . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativeImagePath);
		if (!is_file($full) || !is_readable($full)) {
			return false;
		}
		$raw = @file_get_contents($full);
		if ($raw === false || $raw === '') {
			return false;
		}
		if ($mimeType === '' || strpos($mimeType, '/') === false) {
			try {
				$mimeType = \App\Fields\File::loadFromPath($full)->getMimeType();
			} catch (\Throwable $e) {
				$mimeType = 'application/octet-stream';
			}
		}
		$dataUri = 'data:' . $mimeType . ';base64,' . base64_encode($raw);
		$sidecarFull = $full . self::getUserPhotoBase64SidecarSuffix();
		return false !== @file_put_contents($sidecarFull, $dataUri, LOCK_EX);
	}

	/**
	 * Inline img tag for PDF/HTML templates (data URI). Uses sidecar file with suffix .base64 when present; otherwise builds it once from the image file.
	 */
	public function getUserPhotoImgHtmlForGenerator(): string
	{
		$rel = $this->getAttachedImageRelativePath();
		if ($rel === null || $rel === '') {
			return '';
		}
		$full = ROOT_DIRECTORY . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, ltrim(str_replace('\\', '/', $rel), '/'));
		$sidecarFull = $full . self::getUserPhotoBase64SidecarSuffix();
		$dataUri = '';
		if (is_readable($sidecarFull)) {
			$dataUri = trim((string) @file_get_contents($sidecarFull));
		}
		if ($dataUri === '' || strpos($dataUri, 'data:') !== 0) {
			$mime = 'image/jpeg';
			try {
				$mime = \App\Fields\File::loadFromPath($full)->getMimeType();
			} catch (\Throwable $e) {
				// keep default
			}
			self::writeUserPhotoBase64SidecarForRelativeImage($rel, $mime);
			if (is_readable($sidecarFull)) {
				$dataUri = trim((string) @file_get_contents($sidecarFull));
			}
		}
		if ($dataUri === '' || strpos($dataUri, 'data:') !== 0) {
			return '';
		}
		return '<img src="' . \App\Security\Purifier::encodeHtml($dataUri) . '" alt="" class="user-photo-inline" style="max-height:120px;max-width:120px;" />';
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
	 * @param mixed $imageId
	 */
	public function deleteImage($imageId)
	{
		$db = \App\Database\PearDatabase::getInstance();

		$checkResult = $db->pquery('SELECT smid FROM vtiger_salesmanattachmentsrel WHERE attachmentsid = ?', [$imageId]);
		if (!$db->getRowCount($checkResult)) {
			return false;
		}
		$smId = $db->query_result($checkResult, 0, 'smid');

		if ($this->getId() === $smId) {
			$attRes = $db->pquery('SELECT path, name FROM vtiger_attachments WHERE attachmentsid = ?', [$imageId]);
			if ($db->getRowCount($attRes)) {
				$path = $db->query_result($attRes, 0, 'path');
				$name = $db->query_result($attRes, 0, 'name');
				self::unlinkUserPhotoAndSidecarByRelativePath(self::userImageRelativePathFromAttachmentMeta($path, $imageId, $name));
			}
			$db->pquery('DELETE FROM vtiger_attachments WHERE attachmentsid = ?', [$imageId]);
			$db->pquery('DELETE FROM vtiger_salesmanattachmentsrel WHERE attachmentsid = ?', [$imageId]);
			return true;
		}
		return false;
	}

	/**
	 * Function to get the Day Starts picklist values
	 * @param string $name Description
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
		return \App\Security\PrivilegeUtil::getUserGroups($userId);
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
		$activityReminder = $this->get('reminder_interval');
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
	 * @return \App\Modules\Users\Models\Record
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
		$db = \App\Db\Db::getInstance();
		$db->createCommand()->update('vtiger_crmentity', ['smcreatorid' => $newOwnerId, 'smownerid' => $newOwnerId], ['smcreatorid' => $userId, 'setype' => 'ModComments'])->execute();
		//update history details in vtiger_modtracker_basic
		$db->createCommand()->update('vtiger_modtracker_basic', ['whodid' => $newOwnerId], ['whodid' => $userId])->execute();
		//update comments details in vtiger_modcomments
		$db->createCommand()->update('vtiger_modcomments', ['userid' => $newOwnerId], ['userid' => $userId])->execute();
		$db->createCommand()->delete('vtiger_users', ['id' => $userId])->execute();
		\App\Modules\Settings\SharingAccess\Models\Rule::deleteUserRelatedSharingRules($userId);
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
	 * Hash a plaintext password for storage in vtiger_users.user_password.
	 * Routes through \App\Security\PasswordCrypto (Argon2id + HMAC-SHA-256
	 * pepper). The legacy username-derived crypt() salt is gone.
	 *
	 * @param string $password
	 * @return string
	 */
	public function encryptPassword($password)
	{
		return \App\Security\PasswordCrypto::hash((string) $password);
	}

	/**
	 * Authenticate a user. LDAP path is unchanged; the SQL path now uses
	 * peppered Argon2id verification and silently rehashes the stored value
	 * when parameters are tuned upward.
	 *
	 * @param string $userPassword
	 * @return bool
	 */
	public function doLogin($userPassword)
	{
		$userName = (string)$this->get('user_name');
		$userInfo = (new \App\Db\Query())->select(['id', 'deleted', 'user_password', 'status'])->from('vtiger_users')->where(['user_name' => $userName])->one();
		if (!$userInfo || (int) $userInfo['deleted'] !== 0) {
			\App\Log\Log::error('User not found: ' . $userName);
			return false;
		}
		\App\Log\Log::trace('Start of authentication for user: ' . $userName);
		if ($userInfo['status'] !== 'Active') {
			\App\Log\Log::trace("Authentication failed. User: $userName");
			return false;
		}
		$this->setId((int) $userInfo['id']);
		if (\App\Cache\Cache::has('Authorization', 'config')) {
			$auth = \App\Cache\Cache::get('Authorization', 'config');
		} else {
			$dataReader = (new \App\Db\Query())->from('yetiforce_auth')->createCommand()->query();
			$auth = [];
			while ($row = $dataReader->read()) {
				$auth[$row['type']][$row['param']] = $row['value'];
			}
			\App\Cache\Cache::save('Authorization', 'config', $auth);
		}
		if ($auth['ldap']['active'] == 'true') {
			\App\Log\Log::trace('Start LDAP authentication');
			$users = explode(',', $auth['ldap']['users']);
			if (in_array($userInfo['id'], $users)) {
				$bind = false;
				$port = $auth['ldap']['port'] == '' ? 389 : $auth['ldap']['port'];
				$ds = @ldap_connect($auth['ldap']['server'], $port);
				if (!$ds) {
					\App\Log\Log::error('Error LDAP authentication: Could not connect to LDAP server.');
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
					\App\Log\Log::error('LDAP authentication: LDAP bind failed.');
				}
				$this->authenticated = $bind;
				return $bind;
			} else {
				\App\Log\Log::trace($userName . ' user does not belong to the LDAP');
			}
			\App\Log\Log::trace('End LDAP authentication');
		}

		\App\Log\Log::trace('Using integrated/SQL authentication');
		if (!\App\Security\PasswordCrypto::verify((string) $userPassword, (string) $userInfo['user_password'])) {
			\App\Log\Log::trace("Authentication failed. User: $userName");
			$this->authenticated = false;
			return false;
		}
		if (\App\Security\PasswordCrypto::needsRehash((string) $userInfo['user_password'])) {
			try {
				$rehash = \App\Security\PasswordCrypto::hash((string) $userPassword);
				\App\Db\Db::getInstance()->createCommand()
					->update('vtiger_users', ['user_password' => $rehash], ['id' => (int) $userInfo['id']])
					->execute();
				\App\Log\Log::trace('Password hash silently upgraded to current Argon2id parameters for user: ' . $userName);
			} catch (\Throwable $e) {
				// Do not block login if the rehash write fails; just log it.
				\App\Log\Log::warning('Password rehash on login failed: ' . $e->getMessage());
			}
		}
		\App\Log\Log::trace("Authentication OK. User: $userName");
		$this->authenticated = true;
		return true;
	}

	/**
	 * Verify a plaintext password against the currently stored hash.
	 *
	 * @param string $password
	 * @return bool
	 */
	public function verifyPassword($password)
	{
		$row = (new \App\Db\Query())->select(['user_password'])->from('vtiger_users')->where(['id' => $this->getId()])->one();
		if (!$row) {
			return false;
		}
		return \App\Security\PasswordCrypto::verify((string) $password, (string) $row['user_password']);
	}

	/**
	 * Verify the current password (if non-admin) and persist the new one.
	 * No `confirm_password` mirror, no `crypt_type` selector - the Argon2id
	 * hash is self-describing.
	 *
	 * @param string $userPassword
	 * @param string $newPassword
	 * @return bool
	 */
	public function changePassword($userPassword, $newPassword)
	{
		$userName = (string)$this->get('user_name');
		$currentUser = \App\User\CurrentUser::get();
		\App\Log\Log::trace('Starting password change for ' . $userName);

		if (empty($newPassword)) {
			$this->error_string = \App\Runtime\Vtiger_Language_Handler::translate('ERR_PASSWORD_CHANGE_FAILED_1') . $userName . \App\Runtime\Vtiger_Language_Handler::translate('ERR_PASSWORD_CHANGE_FAILED_2');
			return false;
		}
		if (!$currentUser->isAdmin()) {
			if (!$this->verifyPassword($userPassword)) {
				\App\Log\Log::warning('Incorrect old password for ' . $userName);
				$this->error_string = \App\Runtime\Vtiger_Language_Handler::translate('ERR_PASSWORD_INCORRECT_OLD');
				return false;
			}
		}

		$encryptedNewPassword = \App\Security\PasswordCrypto::hash((string) $newPassword);

		\App\Db\Db::getInstance()->createCommand()->update('vtiger_users', [
			'user_password' => $encryptedNewPassword,
			], ['id' => $this->getId()])->execute();

		$this->set('user_password', $encryptedNewPassword);
		$this->set('confirm_password', '');

		\App\Log\Log::trace('Ending password change for ' . $userName);
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
			\App\Log\Log::trace("Saving To Preferences:" . $name . "=" . $value);
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
		\App\Db\Db::getInstance()->createCommand()
			->update('vtiger_users', ['user_preferences' => $data], ['id' => $this->getId()])
			->execute();
		\App\Log\Log::trace("SAVING: PREFERENCES SIZE " . strlen($data));
		$_SESSION["USER_PREFERENCES"] = $this->user_preferences;
	}

	/**
	 * Function to load the user preferences from db
	 * @param string $value - serialized preferences data
	 */
	public function loadPreferences($value)
	{
		if (isset($value) && !empty($value)) {
			\App\Log\Log::trace("LOADING :PREFERENCES SIZE " . strlen($value));
			$this->user_preferences = unserialize(base64_decode($value));
			$_SESSION = array_merge($this->user_preferences, $_SESSION);
			\App\Log\Log::trace("Finished Loading");
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
		if (\App\Cache\Cache::has('UserIsExists', $id)) {
			return \App\Cache\Cache::get('UserIsExists', $id);
		}
		$isExists = false;
		if (\App\Core\AppConfig::performance('ENABLE_CACHING_USERS')) {
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
		\App\Cache\Cache::save('UserIsExists', $id, $isExists);
		return $isExists;
	}

	/**
	 * Function to get the user if of the active admin user.
	 * @return integer - Active Admin User ID
	 */
	public static function getActiveAdminId()
	{
		$key = 'id';
		if (\App\Cache\Cache::has(__METHOD__, $key)) {
			return \App\Cache\Cache::get(__METHOD__, $key);
		} else {
			$adminId = 1;
			if (\App\Core\AppConfig::performance('ENABLE_CACHING_USERS')) {
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
						->orderBy(['id' => SORT_ASC])
						->limit(1)->scalar();
			}
			\App\Cache\Cache::save(__METHOD__, $key, $adminId, \App\Cache\Cache::LONG);
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
		if (\App\Cache\Cache::has(__METHOD__, $name)) {
			return \App\Cache\Cache::get(__METHOD__, $name);
		}
		$userId = (new \App\Db\Query())->select('id')
				->from('vtiger_users')
				->where(['user_name' => $name])
				->limit(1)->scalar();
		\App\Cache\Cache::save(__METHOD__, $name, $userId, \App\Cache\Cache::LONG);
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
			\App\Log\Log::error("User privileges file not found for user: $userid");
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
			return 1;
		}else {
			return -1;
		}
	}

	/**
	 * Function to get userid and username of all vtiger_users
	 * @return array User Array in the following format:
	 * $userArray=Array($userid1=>$username, $userid2=>$username,............,$useridn=>$username)
	 */
	public static function getAllUserName()
	{
		\App\Log\Log::trace("Entering getAllUserName() method ...");
		$adb = \App\Database\PearDatabase::getInstance();
		$query = "select * from vtiger_users where deleted=0";
		$result = $adb->pquery($query, []);
		$num_rows = $adb->num_rows($result);
		$user_details = [];
		for ($i = 0; $i < $num_rows; $i++) {
			$userid = $adb->query_result($result, $i, 'id');
			$username = \vtlib\Deprecated::getFullNameFromQResult($result, $i, 'Users');
			$user_details[$userid] = $username;
		}
		\App\Log\Log::trace("Exiting getAllUserName method ...");
		return $user_details;
	}
}
