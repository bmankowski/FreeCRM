<?php

namespace App\Modules\Settings\ModuleManager\Views;

use App\ModuleManagement\ServiceLocator;


/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * *********************************************************************************** */

class ModuleImport extends \App\Modules\Settings\Base\Views\Index
{

	public function __construct()
	{
		parent::__construct();
		$this->exposeMethod('importUserModuleStep1');
		$this->exposeMethod('importUserModuleStep2');
		$this->exposeMethod('importUserModuleStep3');
		$this->exposeMethod('updateUserModuleStep3');
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$systemMode = \App\Core\AppConfig::main('systemMode');
		if ($systemMode == 'demo') {
			throw new \App\Exceptions\AppException('LBL_ERROR_IMPORT_IN_DEMO');
		}

		$mode = $request->getMode();
		if (!empty($mode)) {
			$this->invokeExposedMethod($mode, $request);
			return;
		}

		$qualifiedModuleName = $request->getModule(false);
		$viewer = $this->getViewer($request);
		$viewer->assign('QUALIFIED_MODULE', $qualifiedModuleName);
		$viewer->view('Step1.tpl', $qualifiedModuleName);
	}

	/**
	 * Function to get the list of Script models to be included
	 * @param \App\Http\Vtiger_Request $request
	 * @return <Array> - List of ScriptAsset instances
	 */
	public function getFooterScripts(\App\Http\Vtiger_Request $request)
	{
		$headerScriptInstances = parent::getFooterScripts($request);
		$moduleName = $request->getModule();

		$jsFileNames = array(
			"modules.Settings.$moduleName.resources.ModuleImport"
		);

		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		$headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);
		return $headerScriptInstances;
	}

	public function importUserModuleStep1(\App\Http\Vtiger_Request $request)
	{
		$viewer = $this->getViewer($request);
		$qualifiedModuleName = $request->getModule(false);
		
		// Prepare upload size limits for template
		$maxUploadBytes = min(
			\App\Modules\Base\Helpers\Util::parseHumanReadableToBytes(ini_get('upload_max_filesize')),
			\App\Modules\Base\Helpers\Util::parseHumanReadableToBytes(ini_get('post_max_size'))
		);
		$maxUploadSizeHuman = \App\Modules\Base\Helpers\Util::formatBytesToHumanReadable($maxUploadBytes);
		$isUploadLimitTooSmall = $maxUploadBytes < 5242880; // 5MB in bytes
		
		$viewer->assign('QUALIFIED_MODULE', $qualifiedModuleName);
		$viewer->assign('MAX_UPLOAD_SIZE_BYTES', $maxUploadBytes);
		$viewer->assign('MAX_UPLOAD_SIZE_HUMAN', $maxUploadSizeHuman);
		$viewer->assign('MAX_UPLOAD_SIZE_TOO_SMALL', $isUploadLimitTooSmall);
		
		// Prepare JSON validator data for template
		$viewer->assign('UPLOAD_MODULE_ZIP_VALIDATOR', \App\Utils\Json::encode([['name'=>'UploadModuleZip']]));
		
		if ($request->isAjax()) {
			$viewer->view('ImportUserModuleStep1.tpl', $qualifiedModuleName);
			return;
		}

		$viewer->assign('CURRENT_VIEW_TEMPLATE', 'ImportUserModuleStep1.tpl');
		$viewer->view('ModuleImportIndex.tpl', $qualifiedModuleName);
	}

	public function importUserModuleStep2(\App\Http\Vtiger_Request $request)
	{
		$viewer = $this->getViewer($request);
		$uploadDir = \App\Modules\Settings\ModuleManager\Models\Module::getUploadDirectory();
		$qualifiedModuleName = $request->getModule(false);
		$viewer->assign('MODULEIMPORT_EXISTS', "false");
		$viewer->assign('MODULEIMPORT_DIR_EXISTS', "false");
		$viewer->assign('MODULEIMPORT_DIR', '');

		$uploadFile = 'usermodule_' . time() . '.zip';
		$uploadFileName = "$uploadDir/$uploadFile";
		$absoluteUploadFile = $this->toAbsolutePath($uploadFileName);
		$error = '';
		$this->ensureUploadDirectory($absoluteUploadFile);
		if (!move_uploaded_file($_FILES['moduleZip']['tmp_name'], $absoluteUploadFile)) {
			$error = 'LBL_ERROR_MOVE_UPLOADED_FILE';
		} else {
			/** @var \App\ModuleManagement\Services\PackageService $package */
			$package = $this->getPackageService();
			if (!$package->checkZip($absoluteUploadFile)) {
				$error = $package->getErrorText();
				$this->safeUnlink($absoluteUploadFile);
			} else {
				$importModuleName = $package->getModuleNameFromZip($absoluteUploadFile);
				$importModuleDepVtVersion = $package->getDependentVtigerVersion();

				if ($importModuleName === null) {
					$error = $package->getErrorText();
					$this->safeUnlink($absoluteUploadFile);
				} else {
					$moduleLicence = \App\Security\Purifier::purify($package->getLicense());

					$viewer->assign('MODULEIMPORT_FILE', $uploadFile);
					$viewer->assign('MODULEIMPORT_TYPE', $package->type());
					$viewer->assign('MODULEIMPORT_TYPE_NAME', $this->getPackageTypeName($package->type()));
					$viewer->assign('MODULEIMPORT_VERSION', $package->getVersion() ?: '—');
					$viewer->assign('MODULEIMPORT_IS_UPDATE', $package->isUpdateType());
					$updateInfo = [];
					if (method_exists($package, 'getUpdateInfo')) {
						$updateInfo = $package->getUpdateInfo();
					}
					$viewer->assign('MODULEIMPORT_UPDATE_INFO', $updateInfo);
					$viewer->assign('MODULEIMPORT_NAME', $importModuleName);
					$viewer->assign('MODULEIMPORT_DEP_VTVERSION', $importModuleDepVtVersion);
					$viewer->assign('MODULEIMPORT_LICENSE', $moduleLicence);
					$viewer->assign('MODULEIMPORT_PARAMETERS', $package->getParameters());

					if (!$package->isLanguageType() && !$package->isUpdateType() && !$package->isModuleBundle()) {
						$moduleInstance = \App\Modules\Base\Models\Module::getInstance($importModuleName);
						$moduleimport_exists = ($moduleInstance) ? "true" : "false";
						$moduleimport_dir_name = "modules/$importModuleName";
						$moduleimport_dir_exists = (is_dir($moduleimport_dir_name) ? "true" : "false");
						$viewer->assign('MODULEIMPORT_EXISTS', $moduleimport_exists);
						$viewer->assign('MODULEIMPORT_DIR', $moduleimport_dir_name);
						$viewer->assign('MODULEIMPORT_DIR_EXISTS', $moduleimport_dir_exists);
					}
				}
			}
		}
		$viewer->assign('MODULEIMPORT_ERROR', $error);
		$viewer->view('ImportUserModuleStep2.tpl', $qualifiedModuleName);
	}

	public function importUserModuleStep3(\App\Http\Vtiger_Request $request)
	{
		$viewer = $this->getViewer($request);
		$qualifiedModuleName = $request->getModule(false);
		$importModuleName = $request->get('module_import_name');
		$uploadFile = $request->get('module_import_file');
		$uploadDir = \App\Modules\Settings\ModuleManager\Models\Module::getUploadDirectory();
		$uploadFileName = "$uploadDir/$uploadFile";
		$absoluteUploadFile = $this->toAbsolutePath($uploadFileName);
		$importError = '';

		try {
			/** @var \App\ModuleManagement\Services\PackageService $package */
			$package = $this->getPackageService();
			$package->import($absoluteUploadFile);
			if ($package->getErrorText()) {
				$importError = $package->getErrorText();
			}
			$viewer->assign('IMPORT_MODULE_TYPE', $this->getPackageTypeName($package->type()));
		} catch (\Throwable $exception) {
			$importError = $exception->getMessage();
		}

		$this->safeUnlink($absoluteUploadFile);
		if ($importError !== '') {
			$viewer->assign('MODULEIMPORT_ERROR', $importError);
		}

		$viewer->assign("IMPORT_MODULE_NAME", $importModuleName);
		$viewer->assign('QUALIFIED_MODULE', $qualifiedModuleName);
		$viewer->view('ImportUserModuleStep3.tpl', $qualifiedModuleName);
	}

	public function updateUserModuleStep3(\App\Http\Vtiger_Request $request)
	{
		$viewer = $this->getViewer($request);
		$qualifiedModuleName = $request->getModule(false);
		$importModuleName = $request->get('module_import_name');
		$uploadFile = $request->get('module_import_file');
		$uploadDir = \App\Modules\Settings\ModuleManager\Models\Module::getUploadDirectory();
		$uploadFileName = "$uploadDir/$uploadFile";
		$absoluteUploadFile = $this->toAbsolutePath($uploadFileName);

		try {
			/** @var \App\ModuleManagement\Services\PackageService $package */
			$package = $this->getPackageService();
			$moduleService = ServiceLocator::getModuleService();
			$moduleInstance = $moduleService->getInstance($importModuleName);
			if (!$moduleInstance) {
				throw new \App\Exceptions\AppException("Module $importModuleName does not exist.");
			}
			$package->update($moduleInstance, $absoluteUploadFile);
			if ($package->getErrorText()) {
				$viewer->assign('MODULEIMPORT_ERROR', $package->getErrorText());
			}
		} catch (\Throwable $exception) {
			$viewer->assign('MODULEIMPORT_ERROR', $exception->getMessage());
		}

		$this->safeUnlink($absoluteUploadFile);

		$viewer->assign("UPDATE_MODULE_NAME", $importModuleName);
		$viewer->assign('QUALIFIED_MODULE', $qualifiedModuleName);
		$viewer->view('UpdateUserModuleStep3.tpl', $qualifiedModuleName);
	}

	public function validateRequest(\App\Http\Vtiger_Request $request)
	{
		$request->validateReadAccess();
	}

	private function getPackageService(): \App\ModuleManagement\Services\PackageService
	{
		return ServiceLocator::getPackageService();
	}

	private function toAbsolutePath(string $relativePath): string
	{
		$relativePath = ltrim($relativePath, DIRECTORY_SEPARATOR);
		return ROOT_DIRECTORY . DIRECTORY_SEPARATOR . $relativePath;
	}

	private function ensureUploadDirectory(string $absolutePath): void
	{
		$directory = dirname($absolutePath);
		if (!is_dir($directory)) {
			mkdir($directory, 0755, true);
		}
	}

	private function safeUnlink(string $path): void
	{
		if (is_file($path)) {
			@unlink($path);
		}
	}

	private function getPackageTypeName(?string $type): string
	{
		if (!$type) {
			return 'Module';
		}
		$map = [
			'language' => 'Language',
			'layout' => 'Layout',
			'extension' => 'Extension',
			'update' => 'Update',
			'inventory' => 'Inventory',
		];
		$key = strtolower($type);
		return $map[$key] ?? ucfirst($key);
	}
}
