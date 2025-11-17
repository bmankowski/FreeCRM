<?php
/**
 * CLI script to import a module package
 * 
 * Usage: php import_module.php <path_to_zip_file>
 */

// Determine project root directory
$rootDirectory = dirname(__FILE__);
chdir($rootDirectory);

// Bootstrap the application
require_once $rootDirectory . '/vendor/autoload.php';
require_once $rootDirectory . '/vendor/yiisoft/yii2/Yii.php';
require_once $rootDirectory . '/config/api.php';
require_once $rootDirectory . '/config/config.php';
\App\Core\AppConfig::init($API_CONFIG);
\App\Core\Loader::register();

// Initialize services (minimal for CLI)
\App\Cache\Cache::init();
\App\Db\Db::$connectCache = \App\Core\AppConfig::performance('ENABLE_CACHING_DB_CONNECTION');
\App\Log\Log::$logToProfile = false; // Disable profile logging in CLI
\App\Log\Log::$logToConsole = true; // Enable console logging for CLI
\App\Log\Log::$logToFile = \App\Core\AppConfig::debug('LOG_TO_FILE');

// Get ZIP file path from command line
if (!isset($_SERVER['argv'][1])) {
	echo "Usage: php import_module.php <path_to_zip_file>\n";
	echo "Example: php import_module.php ProjektyRekrutacyjne.zip\n";
	exit(1);
}

$zipFile = $_SERVER['argv'][1];

// Check if file exists
if (!file_exists($zipFile)) {
	echo "Error: ZIP file not found: $zipFile\n";
	exit(1);
}

// Get absolute path
$zipFile = realpath($zipFile);

echo "Importing module from: $zipFile\n";

try {
	// Get PackageService instance
	$packageService = \App\ModuleManagement\ServiceLocator::getPackageService();
	
	// Check if ZIP is valid
	if (!$packageService->checkZip($zipFile)) {
		$error = $packageService->getErrorText();
		echo "Error: Invalid package file: $error\n";
		exit(1);
	}
	
	// Get module name
	$moduleName = $packageService->getModuleNameFromZip($zipFile);
	if ($moduleName === null) {
		echo "Error: Cannot determine module name from package\n";
		exit(1);
	}
	
	echo "Module name: $moduleName\n";
	
	// Check if module already exists
	$moduleService = \App\ModuleManagement\ServiceLocator::getModuleService();
	$existingModule = $moduleService->getInstance($moduleName);
	
	if ($existingModule) {
		echo "Warning: Module '$moduleName' already exists.\n";
		echo "Updating existing module instead of importing new one.\n";
		
		// Update existing module
		echo "Starting update...\n";
		$packageService->update($existingModule, $zipFile, true); // overwrite = true
	} else {
		// Import new module
		echo "Starting import...\n";
		$packageService->import($zipFile, true); // overwrite = true
	}
	
	echo "Import completed successfully!\n";
	echo "Module '$moduleName' has been imported.\n";
	
} catch (\Exception $e) {
	echo "Error during import: " . $e->getMessage() . "\n";
	echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
	exit(1);
}

