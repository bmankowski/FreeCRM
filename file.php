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
require_once ROOT_DIRECTORY . '/config/api.php';
require_once ROOT_DIRECTORY . '/config/config.php';
\App\Core\AppConfig::init($API_CONFIG);
\App\Core\Loader::register(); // For legacy modules

// Initialize WebUI services (cache, debugger, error handlers)
\App\EntryPoint\WebUI::initialize();
// Never echo PHP warnings/notices for binary/file responses.
ini_set('display_errors', '0');

try {
	$webUI = new \App\Main\File();
	$request = new \App\Http\Vtiger_Request($_REQUEST, $_REQUEST);
	$webUI->process($request);
} catch (\Throwable $e) {
	\App\Log\Log::error($e->getMessage() . ' => ' . $e->getFile() . ':' . $e->getLine());
	$code = (int) $e->getCode();
	// Preserve intended HTTP status codes when exceptions provide them (e.g. 401/403/404/406/405).
	if ($code >= 400 && $code < 600) {
		http_response_code($code);
	} else {
		http_response_code(400);
	}
}




