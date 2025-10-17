<?php

namespace App\Modules\Users\Actions;
use App\Modules\Settings\PasswordModels\Record;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * *********************************************************************************** */

require_once ROOT_DIRECTORY . '/src/Webservices/Custom/ChangePassword.php';

class SaveAjax extends \App\Modules\Vtiger\Actions\Save
{

	public function __construct()
	{
		parent::__construct();
		$this->exposeMethod('userExists');
		$this->exposeMethod('savePassword');
		$this->exposeMethod('restoreUser');
		$this->exposeMethod('editPasswords');
		$this->exposeMethod('updateUserColor');
		$this->exposeMethod('updateGroupColor');
		$this->exposeMethod('updateModuleColor');
		$this->exposeMethod('updateColorForProcesses');
		$this->exposeMethod('generateColor');
		$this->exposeMethod('activeColor');
		$this->exposeMethod('changeAccessKey');
	}

	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		$currentUserModel = \App\Modules\Users\Models\Record::getCurrentUserModel();
		$userId = $request->get('userid');
		if (!$currentUserModel->isAdminUser()) {
			$mode = $request->getMode();
			if ($mode == 'savePassword' && (isset($userId) && $currentUserModel->getId() != $userId)) {
				throw new \Exception\NoPermittedToRecord('LBL_PERMISSION_DENIED');
			} else if ($mode != 'savePassword' && ($currentUserModel->getId() != $request->get('record'))) {
				throw new \Exception\NoPermittedToRecord('LBL_PERMISSION_DENIED');
			}
		}
	}

	/**
	 * Process
	 * @param Vtiger_Request $request
	 */
	public function process(\App\Http\Vtiger_Request $request)
	{

		$mode = $request->get('mode');
		if (!empty($mode)) {
			$this->invokeExposedMethod($mode, $request);
			return;
		}

		$recordModel = $this->saveRecord($request);
		$settingsModuleModel = Settings_\App\Modules\Users\Models\Module::getInstance();
		$settingsModuleModel->refreshSwitchUsers();
		$fieldModelList = $recordModel->getModule()->getFields();
		$result = [];
		foreach ($fieldModelList as $fieldName => &$fieldModel) {
			if (!$fieldModel->isViewEnabled()) {
				continue;
			}
			$fieldValue = $displayValue = \App\Modules\Vtiger\Util::toSafeHTML($recordModel->get($fieldName));
			if ($fieldModel->getFieldDataType() !== 'currency') {
				$displayValue = $fieldModel->getDisplayValue($fieldValue, $recordModel->getId());
			}
			if ($fieldName === 'language') {
				$displayValue = \App\Runtime\Vtiger_Language_Handler::getLanguageLabel($fieldValue);
			}
			if (($fieldName === 'currency_decimal_separator' || $fieldName === 'currency_grouping_separator') && ($displayValue === '&nbsp;')) {
				$displayValue = \App\Runtime\Vtiger_Language_Handler::translate('LBL_SPACE', 'Users');
			}
			$result[$fieldName] = ['value' => $fieldValue, 'display_value' => $displayValue];
		}

		$result['_recordLabel'] = $recordModel->getName();
		$result['_recordId'] = $recordModel->getId();

		$response = new \App\Http\Vtiger_Response();
		$response->setEmitType(\App\Http\Vtiger_Response::$EMIT_JSON);
		$response->setResult($result);
		$response->emit();
	}

	/**
	 * Function to get the record model based on the request parameters
	 * @param Vtiger_Request $request
	 * @return \App\Modules\Vtiger\Models\Record or Module specific Record Model instance
	 */
	public function getRecordModelFromRequest(\App\Http\Vtiger_Request $request)
	{
		$recordModel = parent::getRecordModelFromRequest($request);
		$fieldName = $request->get('field');
		$currentUserModel = \App\Modules\Users\Models\Record::getCurrentUserModel();
		if ($fieldName === 'is_admin' && (!$currentUserModel->isAdminUser() || !$request->get('value'))) {
			$recordModel->set($fieldName, 'off');
			$recordModel->set('is_owner', 0);
		} else if ($fieldName === 'is_admin' && $currentUserModel->isAdminUser()) {
			$recordModel->set($fieldName, 'on');
			$recordModel->set('is_owner', 1);
		}
		return $recordModel;
	}

	public function userExists(\App\Http\Vtiger_Request $request)
	{
		$module = $request->getModule();
		$userName = $request->get('user_name');
		$userModuleModel = \App\Modules\Users\Models\Module::getCleanInstance($module);
		$status = $userModuleModel->checkDuplicateUser($userName);
		$response = new \App\Http\Vtiger_Response();
		$response->setResult($status);
		$response->emit();
	}

	public function savePassword(\App\Http\Vtiger_Request $request)
	{
		$module = $request->getModule();
		$userModel = vglobal('current_user');
		$newPassword = $request->get('new_password');
		$oldPassword = $request->get('old_password');
		$checkPassword = \App\Modules\Settings\Password\Models\Record::checkPassword($newPassword);
		if (!$checkPassword) {
			$wsUserId = vtws_getWebserviceEntityId($module, $request->get('userid'));
			$wsStatus = vtws_changePassword($wsUserId, $oldPassword, $newPassword, $newPassword, $userModel);
		}
		$response = new \App\Http\Vtiger_Response();
		if ($checkPassword) {
			$response->setError($checkPassword, $checkPassword);
		} elseif ($wsStatus['message']) {
			$response->setResult($wsStatus);
		} else {
			$response->setError('JS_PASSWORD_INCORRECT_OLD', 'JS_PASSWORD_INCORRECT_OLD');
		}
		$response->emit();
	}

	/**
	 * Mass edit users passwords
	 * @param Vtiger_Request $request
	 * @throws WebServiceException
	 */
	public function editPasswords(\App\Http\Vtiger_Request $request)
	{
		$module = $request->getModule();
		$userModel = vglobal('current_user');
		$newPassword = $request->get('new_password');
		$oldPassword = $request->get('old_password');
		$userIds = $request->get('userids');

		$checkPassword = \App\Modules\Settings\Password\Models\Record::checkPassword($newPassword);

		if (!$checkPassword) {
			foreach ($userIds as $userId) {
				$wsUserId = vtws_getWebserviceEntityId($module, $userId);
				$wsStatus = vtws_changePassword($wsUserId, $oldPassword, $newPassword, $newPassword, $userModel);
			}
		}

		$response = new \App\Http\Vtiger_Response();
		if ($checkPassword) {
			$response->setError($checkPassword, $checkPassword);
		} else if ($wsStatus['message']) {
			$response->setResult($wsStatus);
		} else {
			$response->setError('JS_PASSWORD_INCORRECT_OLD', 'JS_PASSWORD_INCORRECT_OLD');
		}

		$response->emit();
	}
	/*
	 * To restore a user
	 * @param Vtiger_Request Object
	 */

	public function restoreUser(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$record = $request->get('userid');

		$recordModel = \App\Modules\Users\Models\Record::getInstanceById($record, $moduleName);
		$recordModel->set('status', 'Active');
		$recordModel->save();

		$db = \App\database\PearDatabase::getInstance();
		$db->pquery("UPDATE vtiger_users SET deleted=? WHERE id=?", array(0, $record));

		$userModuleModel = \App\Modules\Users\Models\Module::getInstance($moduleName);
		$listViewUrl = $userModuleModel->getListViewUrl();

		$response = new \App\Http\Vtiger_Response();
		$response->setResult(array('message' => \App\Runtime\Vtiger_Language_Handler::translate('LBL_USER_RESTORED_SUCCESSFULLY', $moduleName), 'listViewUrl' => $listViewUrl));
		$response->emit();
	}

	public function updateUserColor(\App\Http\Vtiger_Request $request)
	{
		$params = $request->get('params');
		\App\Modules\Users\Models\Colors::updateUserColor($params);
		$response = new \App\Http\Vtiger_Response();
		$response->setResult(array(
			'success' => true,
			'message' => \App\Runtime\Vtiger_Language_Handler::translate('LBL_SAVE_COLOR', $request->getModule(false))
		));
		$response->emit();
	}

	public function updateGroupColor(\App\Http\Vtiger_Request $request)
	{
		$params = $request->get('params');
		\App\Modules\Users\Models\Colors::updateGroupColor($params);
		$response = new \App\Http\Vtiger_Response();
		$response->setResult(array(
			'success' => true,
			'message' => \App\Runtime\Vtiger_Language_Handler::translate('LBL_SAVE_COLOR', $request->getModule(false))
		));
		$response->emit();
	}

	public function updateModuleColor(\App\Http\Vtiger_Request $request)
	{
		$params = $request->get('params');
		\App\Modules\Users\Models\Colors::updateModuleColor($params);
		$response = new \App\Http\Vtiger_Response();
		$response->setResult(array(
			'success' => true,
			'message' => \App\Runtime\Vtiger_Language_Handler::translate('LBL_SAVE_COLOR', $request->getModule(false))
		));
		$response->emit();
	}

	public function generateColor(\App\Http\Vtiger_Request $request)
	{
		$params = $request->get('params');

		$response = new \App\Http\Vtiger_Response();
		$response->setResult([
			'success' => true,
			'color' => \App\Modules\Users\Models\Colors::generateColor($params),
			'message' => \App\Runtime\Vtiger_Language_Handler::translate('LBL_GENERATED_COLOR', $request->getModule(false))
		]);
		$response->emit();
	}

	public function updateColorForProcesses(\App\Http\Vtiger_Request $request)
	{
		$params = $request->get('params');
		\App\Modules\Users\Models\Colors::updateColor($params);
		$response = new \App\Http\Vtiger_Response();
		$response->setResult(array(
			'success' => true,
			'message' => \App\Runtime\Vtiger_Language_Handler::translate('LBL_SAVE_COLOR', $request->getModule(false))
		));
		$response->emit();
	}

	public function activeColor(\App\Http\Vtiger_Request $request)
	{
		$params = $request->get('params');
		$color = \App\Modules\Users\Models\Colors::activeColor($params);
		$response = new \App\Http\Vtiger_Response();
		$response->setResult(array(
			'success' => true,
			'color' => $color,
			'message' => \App\Runtime\Vtiger_Language_Handler::translate('LBL_SAVE_COLOR', $request->getModule(false))
		));
		$response->emit();
	}

	public function changeAccessKey(\App\Http\Vtiger_Request $request)
	{
		$recordId = $request->get('record');
		$moduleName = $request->getModule();

		$response = new \App\Http\Vtiger_Response();
		try {
			$recordModel = \App\Modules\Users\Models\Record::getInstanceById($recordId, $moduleName);
			$oldAccessKey = $recordModel->get('accesskey');

			$entity = $recordModel->getEntity();
			$entity->createAccessKey();

			require_once('src/Modules/Users/CreateUserPrivilegeFile.php');
			createUserPrivilegesfile($recordId);

			require("user_privileges/user_privileges_$recordId.php");
			$newAccessKey = $user_info['accesskey'];
			if ($newAccessKey != $oldAccessKey) {
				$response->setResult(array('message' => \App\Runtime\Vtiger_Language_Handler::translate('LBL_ACCESS_KEY_UPDATED_SUCCESSFULLY', $moduleName), 'accessKey' => $newAccessKey));
			} else {
				$response->setError(\App\Runtime\Vtiger_Language_Handler::translate('LBL_FAILED_TO_UPDATE_ACCESS_KEY', $moduleName));
			}
		} catch (Exception $ex) {
			$response->setError($ex->getMessage());
		}
		$response->emit();
	}
}
