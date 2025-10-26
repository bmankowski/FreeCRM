<?php

namespace App\Modules\Settings\Roles\Actions;


/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * *********************************************************************************** */

class Save extends \App\Runtime\BaseActionController
{

	/**
	 * Checking permission
	 * @param \App\Http\Vtiger_Request $request
	 * @throws \App\Exceptions\AppException
	 */
	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		$currentUser = $request->getUser();
		if (!$currentUser->isAdminUser()) {
			throw new \App\Exceptions\AppException('LBL_PERMISSION_DENIED');
		}
	}

	/**
	 * Process
	 * @param \App\Http\Vtiger_Request $request
	 */
	public function process(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$qualifiedModuleName = $request->getModule(false);
		$recordId = $request->get('record');
		$roleName = $request->get('rolename');
		$allowassignedrecordsto = $request->get('allowassignedrecordsto');

		$moduleModel = \App\Modules\Settings\Vtiger\Models\Module::getInstance($qualifiedModuleName);
		if (!empty($recordId)) {
			$recordModel = \App\Modules\Settings\Roles\Models\Record::getInstanceById($recordId);
		} else {
			$recordModel = new \App\Modules\Settings\Roles\Models\Record();
		}

		$roleProfiles = $request->get('profiles');
		$parentRoleId = $request->get('parent_roleid');
		if ($recordModel && !empty($parentRoleId)) {
			$parentRole = \App\Modules\Settings\Roles\Models\Record::getInstanceById($parentRoleId);
			$recordModel->set('change_owner', $request->get('change_owner'))
				->set('searchunpriv', $request->get('searchunpriv'))
				->set('listrelatedrecord', $request->get('listRelatedRecord'))
				->set('previewrelatedrecord', $request->get('previewRelatedRecord'))
				->set('editrelatedrecord', $request->get('editRelatedRecord'))
				->set('permissionsrelatedfield', $request->get('permissionsRelatedField'))
				->set('globalsearchadv', $request->get('globalSearchAdvanced'))
				->set('assignedmultiowner', $request->get('assignedmultiowner'))
				->set('clendarallorecords', $request->get('clendarallorecords'))
				->set('auto_assign', $request->get('auto_assign'));
			if (!empty($allowassignedrecordsto))
				$recordModel->set('allowassignedrecordsto', $allowassignedrecordsto); // set the value of assigned records to
			if ($parentRole && !empty($roleName) && !empty($roleProfiles)) {
				$recordModel->set('rolename', $roleName);
				$recordModel->set('profileIds', $roleProfiles);
				$parentRole->addChildRole($recordModel);
			}

			//After role updation recreating user privilege files
			if ($roleProfiles) {
				foreach ($roleProfiles as $profileId) {
					$profileRecordModel = \App\Modules\Settings\Profiles\Model\Record::getInstanceById($profileId);
					$profileRecordModel->recalculate(array($recordId));
				}
			}
		}

		$redirectUrl = $moduleModel->getDefaultUrl();
		header("Location: $redirectUrl");
	}

	public function validateRequest(\App\Http\Vtiger_Request $request)
	{
		$request->validateWriteAccess();
	}
}
