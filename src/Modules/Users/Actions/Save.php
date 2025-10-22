<?php

namespace App\Modules\Users\Actions;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

class Save extends \App\Runtime\Vtiger_Action_Controller
{

	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$record = $request->get('record');
		$recordModel = $this->record ? $this->record : \App\Modules\Vtiger\Models\Record::getInstanceById($record, $moduleName);
		$currentUserModel = \App\Modules\Users\Models\Record::getCurrentUserModel();

		// Check for operation access.
		$allowed = \App\Modules\Users\Models\Privileges::isPermitted($moduleName, 'Save', $record);

		if ($allowed) {
			// Deny access if not administrator or account-owner or self
			if (!$currentUserModel->isAdminUser()) {
				if (empty($record)) {
					$allowed = false;
				} else if ($currentUserModel->get('id') !== $recordModel->getId()) {
					$allowed = false;
				}
			}
		}
		if (!$allowed) {
			throw new \Exception\AppException('LBL_PERMISSION_DENIED');
		}
	}

	/**
	 * Function to get the record model based on the request parameters
	 * @param \App\Http\Vtiger_Request $request
	 * @return \App\Modules\Vtiger\Models\Record or Module specific Record Model instance
	 */
	protected function getRecordModelFromRequest(\App\Http\Vtiger_Request $request)
	{
		$recordModel = parent::getRecordModelFromRequest($request);
		if ($recordModel->isNew()) {
			$recordModel->set('user_name', $request->get('user_name', null));
			$recordModel->set('user_password', $request->get('user_password', null));
			$recordModel->set('confirm_password', $request->get('confirm_password', null));
		}
		$homePageComponents = $recordModel->getHomePageComponents();
		$selectedHomePageComponents = $request->get('homepage_components', array());
		foreach ($homePageComponents as $key => $value) {
			if (in_array($key, $selectedHomePageComponents)) {
				$request->setGlobal($key, $key);
			} else {
				$request->setGlobal($key, '');
			}
		}
		return $recordModel;
	}

	/**
	 * Process
	 * @param \App\Http\Vtiger_Request $request
	 * @return boolean
	 */
	public function process(\App\Http\Vtiger_Request $request)
	{
		$result = \App\Modules\Vtiger\Helpers\Util::transformUploadedFiles($_FILES, true);
		$_FILES = $result['imagename'];

		$moduleModel = \App\Modules\Vtiger\Models\Module::getInstance('Users');
		if (!$moduleModel->checkMailExist($request->get('email1'), $request->get('record'))) {
			$recordModel = $this->saveRecord($request);
			$settingsModuleModel = \App\Modules\Settings\Users\Models\Module::getInstance();
			$settingsModuleModel->refreshSwitchUsers();

			$sharedIds = $request->get('sharedusers');
			$sharedType = $request->get('calendarsharedtype');
			$currentUserModel = \App\Modules\Users\Models\Record::getCurrentUserModel();
			$calendarModuleModel = \App\Modules\Vtiger\Models\Module::getInstance('Calendar');
			$accessibleUsers = \App\Fields\Owner::getInstance('Calendar', $currentUserModel)->getAccessibleUsersForModule();

			if ($sharedType == 'private') {
				$calendarModuleModel->deleteSharedUsers($currentUserModel->getId());
			} else if ($sharedType == 'public') {
				$allUsers = $currentUserModel->getAll(true);
				$accessibleUsers = array();
				foreach ($allUsers as $id => $userModel) {
					$accessibleUsers[$id] = $id;
				}
				$calendarModuleModel->deleteSharedUsers($currentUserModel->getId());
				$calendarModuleModel->insertSharedUsers($currentUserModel->getId(), array_keys($accessibleUsers));
			} else {
				if (!empty($sharedIds)) {
					$calendarModuleModel->deleteSharedUsers($currentUserModel->getId());
					$calendarModuleModel->insertSharedUsers($currentUserModel->getId(), $sharedIds);
				} else {
					$calendarModuleModel->deleteSharedUsers($currentUserModel->getId());
				}
			}
			if ($request->get('relationOperation')) {
				$parentRecordModel = \App\Modules\Vtiger\Models\Record::getInstanceById($request->get('sourceRecord'), $request->get('sourceModule'));
				$loadUrl = $parentRecordModel->getDetailViewUrl();
			} else if ($request->get('isPreference')) {
				$loadUrl = $recordModel->getPreferenceDetailViewUrl();
			} else {
				$loadUrl = $recordModel->getDetailViewUrl();
			}
		} else {
			\App\Log::error('USER_MAIL_EXIST');
			header('Location: index.php?module=Users&parent=Settings&view=Edit');
			return false;
		}
		header("Location: $loadUrl");
	}
}
