<?php
/**
 * Basic file to handle files
 * @package YetiForce.Files
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */
define('REQUEST_MODE', 'File');
define('ROOT_DIRECTORY', __DIR__ !== DIRECTORY_SEPARATOR ? __DIR__ : '');

// Bootstrap: Load autoloaders
require_once ROOT_DIRECTORY . '/vendor/autoload.php';  // Composer PSR-4 autoloader
require_once ROOT_DIRECTORY . '/vendor/yiisoft/yii2/Yii.php';
require_once ROOT_DIRECTORY . '/include/Loader.php';
Loader::register();

// Initialize WebUI services (cache, debugger, error handlers)
\App\EntryPoint\WebUI::initialize();

try {
	$webUI = new \App\Main\File();
	$webUI->process(\App\Http\AppRequest::init());
} catch (Exception $e) {
	\App\Log::error($e->getMessage() . ' => ' . $e->getFile() . ':' . $e->getLine());
	//var_dump($e->getMessage());
	header('HTTP/1.1 400 Bad Request');
}




