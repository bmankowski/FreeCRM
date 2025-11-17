<?php

namespace App\Modules\Settings\ModuleManager\Actions;

use App\ModuleManagement\ServiceLocator;

/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * ********************************************************************************** */

class Basic extends \App\Modules\Settings\Base\Views\IndexAjax
{

	public function __construct()
	{
		parent::__construct();
		$this->exposeMethod('updateModuleStatus');
		$this->exposeMethod('importUserModuleStep3');
		$this->exposeMethod('updateUserModuleStep3');
		$this->exposeMethod('checkModuleName');
		$this->exposeMethod('createModule');
		$this->exposeMethod('deleteModule');
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$mode = $request->getMode();
		if (!empty($mode)) {
			echo $this->invokeExposedMethod($mode, $request);
			return;
		}
	}

	public function updateModuleStatus(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->get('forModule');
		$updateStatus = $request->get('updateStatus');
		$moduleManagerModel = new \App\Modules\Settings\ModuleManager\Models\Module();
		$response = new \App\Http\Vtiger_Response();
		try {
			if ($request->getBoolean('updateStatus')) {
				$moduleManagerModel->enableModule($moduleName);
			} else {
				$moduleManagerModel->disableModule($moduleName);
			}
		} catch (\App\Exceptions\NotAllowedMethod $e) {
			$response->setError($e->getMessage());
		}
		$response->emit();
	}

	public function importUserModuleStep3(\App\Http\Vtiger_Request $request)
	{
		$importModuleName = $request->get('module_import_name');
		$uploadFile = $request->get('module_import_file');
		$uploadDir = \App\Modules\Settings\ModuleManager\Models\Module::getUploadDirectory();
		$uploadFileName = "$uploadDir/$uploadFile";
		$absoluteUploadFile = ROOT_DIRECTORY . DIRECTORY_SEPARATOR . ltrim($uploadFileName, DIRECTORY_SEPARATOR);

		$package = ServiceLocator::getPackageService();
		$result = ['success' => true, 'importModuleName' => $importModuleName];
		try {
			$package->import($absoluteUploadFile);
			if ($package->getErrorText()) {
				$result = ['success' => false, 'message' => $package->getErrorText()];
			}
		} catch (\Throwable $exception) {
			$result = ['success' => false, 'message' => $exception->getMessage()];
		}

		if (is_file($absoluteUploadFile)) {
			@unlink($absoluteUploadFile);
		}

		$response = new \App\Http\Vtiger_Response();
		$response->setResult($result);
		$response->emit();
	}

	public function updateUserModuleStep3(\App\Http\Vtiger_Request $request)
	{
		$importModuleName = $request->get('module_import_name');
		$uploadFile = $request->get('module_import_file');
		$uploadDir = \App\Modules\Settings\ModuleManager\Models\Module::getUploadDirectory();
		$uploadFileName = "$uploadDir/$uploadFile";
		$absoluteUploadFile = ROOT_DIRECTORY . DIRECTORY_SEPARATOR . ltrim($uploadFileName, DIRECTORY_SEPARATOR);

		$package = ServiceLocator::getPackageService();
		$moduleService = ServiceLocator::getModuleService();
		$moduleInstance = $moduleService->getInstance($importModuleName);
		$result = ['success' => true, 'importModuleName' => $importModuleName];

		try {
			if (!$moduleInstance) {
				throw new \App\Exceptions\AppException("Module $importModuleName does not exist.");
			}
			$package->update($moduleInstance, $absoluteUploadFile);
			if ($package->getErrorText()) {
				$result = ['success' => false, 'message' => $package->getErrorText()];
			}
		} catch (\Throwable $exception) {
			$result = ['success' => false, 'message' => $exception->getMessage()];
		}

		if (is_file($absoluteUploadFile)) {
			@unlink($absoluteUploadFile);
		}

		$response = new \App\Http\Vtiger_Response();
		$response->setResult($result);
		$response->emit();
	}

	public function validateRequest(\App\Http\Vtiger_Request $request)
	{
		$request->validateWriteAccess();
	}

	public function checkModuleName(\App\Http\Vtiger_Request $request)
	{
		$qualifiedModuleName = $request->getModule(false);
		$moduleName = $request->get('moduleName');
		$module = \App\Modules\Base\Models\Module::getInstance($moduleName);
		if ($module) {
			$result = array('success' => false, 'text' => \App\Runtime\Vtiger_Language_Handler::translate('LBL_MODULE_ALREADY_EXISTS_TRY_ANOTHER', $qualifiedModuleName));
		} elseif (preg_match('/[^A-Za-z]/i', $moduleName)) {
			$result = array('success' => false, 'text' => \App\Runtime\Vtiger_Language_Handler::translate('LBL_INVALID_MODULE_NAME', $qualifiedModuleName));
		} else {
			$result = array('success' => true);
		}
		$response = new \App\Http\Vtiger_Response();
		$response->setResult($result);
		$response->emit();
	}

	public function createModule(\App\Http\Vtiger_Request $request)
	{
		$qualifiedModuleName = $request->getModule(false);
		$formData = $request->get('formData');
		$moduleManagerModel = new \App\Modules\Settings\ModuleManager\Models\Module();
		$result = array('success' => true, 'text' => ucfirst($formData['module_name']));
		try {
			$moduleManagerModel->createModule($formData);
		} catch (Exception $e) {
			$result = array('success' => false, 'text' => $e->getMessage());
		}
		$response = new \App\Http\Vtiger_Response();
		$response->setResult($result);
		$response->emit();
	}

	public function deleteModule(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->get('forModule');
		$moduleInstance = \App\Modules\Base\Models\Module::getInstance($moduleName);
		if ($moduleInstance) {
			$moduleInstance->delete();
			$result = array('success' => true);
		} else
			$result = array('success' => false);
		$response = new \App\Http\Vtiger_Response();
		$response->setResult($result);
		$response->emit();
	}
}
