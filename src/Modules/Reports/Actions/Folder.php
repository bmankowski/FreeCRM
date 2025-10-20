<?php

namespace App\Modules\Reports\Actions;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * *********************************************************************************** */

class Folder extends \App\Runtime\Vtiger_Action_Controller
{

	public function __construct()
	{
		parent::__construct();
		$this->exposeMethod('save');
		$this->exposeMethod('delete');
	}

	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		$currentUserPriviligesModel = \App\Modules\Users\Models\Privileges::getCurrentUserPrivilegesModel();
		if (!$currentUserPriviligesModel->hasModulePermission($request->getModule())) {
			throw new \Exception\NoPermitted('LBL_PERMISSION_DENIED');
		}
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$mode = $request->get('mode');
		if (!empty($mode)) {
			$this->invokeExposedMethod($mode, $request);
			return;
		}
	}

	/**
	 * Function that saves/updates the Folder
	 * @param \App\Http\Vtiger_Request $request
	 */
	public function save(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$folderModel = Reports_Folder_Model::getInstance();
		$folderId = $request->get('folderid');

		if (!empty($folderId)) {
			$folderModel->set('folderid', $folderId);
		}

		$folderModel->set('foldername', $request->get('foldername'));
		$folderModel->set('description', $request->get('description'));

		if ($folderModel->checkDuplicate()) {
			throw new \Exception\AppException(\App\Runtime\Vtiger_Language_Handler::translate('LBL_DUPLICATES_EXIST', $moduleName));
		}

		$folderModel->save();
		$result = array('success' => true, 'message' => \App\Runtime\Vtiger_Language_Handler::translate('LBL_FOLDER_SAVED', $moduleName), 'info' => $folderModel->getInfoArray());

		$response = new \App\Http\Vtiger_Response();
		$response->setResult($result);
		$response->emit();
	}

	/**
	 * Function that deletes the Folder
	 * @param \App\Http\Vtiger_Request $request
	 */
	public function delete(\App\Http\Vtiger_Request $request)
	{
		$folderId = $request->get('folderid');
		$moduleName = $request->getModule();

		if ($folderId) {
			$folderModel = Reports_Folder_Model::getInstanceById($folderId);

			if ($folderModel->isDefault()) {
				$message = \App\Runtime\Vtiger_Language_Handler::translate('LBL_FOLDER_CAN_NOT_BE_DELETED', $moduleName);
			} else {
				if ($folderModel->hasReports()) {
					$message = \App\Runtime\Vtiger_Language_Handler::translate('LBL_FOLDER_NOT_EMPTY', $moduleName);
				}
			}
			if ($message) {
				$result = array('success' => false, 'message' => $message);
			} else {
				$folderModel->delete();
				$result = array('success' => true, 'message' => \App\Runtime\Vtiger_Language_Handler::translate('LBL_FOLDER_DELETED', $moduleName));
			}

			$response = new \App\Http\Vtiger_Response();
			$response->setResult($result);
			$response->emit();
		}
	}

	public function validateRequest(\App\Http\Vtiger_Request $request)
	{
		$request->validateWriteAccess();
	}
}
