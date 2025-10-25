<?php
/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * ********************************************************************************** */

$startTime = microtime(true);

// Support for public directory structure
// If called from public/index.php, __DIR__ will be /path/to/public
// If called directly, __DIR__ will be /path/to/root
$isPublicDir = (basename(__DIR__) === 'public');
if ($isPublicDir) {
	// We're in public/ directory, adjust to root
	$rootDir = dirname(__DIR__);
	chdir($rootDir);
	// Clean up $_SERVER variables to remove /public/ from paths
	if (isset($_SERVER['SCRIPT_FILENAME'])) {
		$_SERVER['SCRIPT_FILENAME'] = str_replace('/public/', '/', $_SERVER['SCRIPT_FILENAME']);
	}
	if (isset($_SERVER['SCRIPT_NAME'])) {
		$_SERVER['SCRIPT_NAME'] = str_replace('/public/', '/', $_SERVER['SCRIPT_NAME']);
	}
	if (isset($_SERVER['PHP_SELF'])) {
		$_SERVER['PHP_SELF'] = str_replace('/public/', '/', $_SERVER['PHP_SELF']);
	}
	define('ROOT_DIRECTORY', $rootDir !== DIRECTORY_SEPARATOR ? $rootDir : '');
} else {
	define('ROOT_DIRECTORY', __DIR__ !== DIRECTORY_SEPARATOR ? __DIR__ : '');
}

define('REQUEST_MODE', 'WebUI');

require ROOT_DIRECTORY . '/src/RequirementsValidation.php';

// Bootstrap: Load autoloaders
require_once ROOT_DIRECTORY . '/vendor/autoload.php';  // Composer PSR-4 autoloader
require_once ROOT_DIRECTORY . '/vendor/yiisoft/yii2/Yii.php';
require_once ROOT_DIRECTORY . '/config/api.php';
require_once ROOT_DIRECTORY . '/config/config.php';
\App\AppConfig::init($API_CONFIG);
\App\Loader::register();  // For Settings modules in old_modules

// Initialize WebUI services (cache, debugger, error handlers)
\App\EntryPoint\WebUI::initialize();


$webUI = new \App\EntryPoint\WebUI();

// Create request instance for this web request
$request = new \App\Http\Vtiger_Request($_REQUEST, $_REQUEST);
if ($request instanceof \App\Http\Vtiger_Request) {
	$webUI->process($request);
}

