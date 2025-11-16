<?php

namespace App\Modules\Cron;

/**
 * Cron Bootstrap - Initializes the cron environment
 * @package App\Modules\Cron
 */
class Bootstrap
{
	/**
	 * Initialize the cron environment
	 * @return void
	 */
	public static function init(): void
	{
		self::defineConstants();
		self::loadDependencies();
		self::initializeServices();
	}

	/**
	 * Define required constants
	 * @return void
	 */
	private static function defineConstants(): void
	{
		if (!defined('ROOT_DIRECTORY')) {
			define('ROOT_DIRECTORY', getcwd() !== DIRECTORY_SEPARATOR ? getcwd() : '');
		}
	}

	/**
	 * Load dependencies
	 * @return void
	 */
	private static function loadDependencies(): void
	{
		require ROOT_DIRECTORY . '/vendor/autoload.php';
		require ROOT_DIRECTORY . '/vendor/yiisoft/yii2/Yii.php';
		
		// Load config files which define $API_CONFIG in global scope
		require ROOT_DIRECTORY . '/config/api.php';
		require ROOT_DIRECTORY . '/config/config.php';
		
		// Access the global variable
		global $API_CONFIG;
		\App\Core\AppConfig::init($API_CONFIG);
		\App\Core\Loader::register();
	}

	/**
	 * Initialize required services
	 * @return void
	 */
	private static function initializeServices(): void
	{
		\App\Debug\Debugger::init();
		\App\Cache\Cache::init();
		\App\Db\Db::$connectCache = \App\Core\AppConfig::performance('ENABLE_CACHING_DB_CONNECTION');
		\App\Log\Log::$logToProfile = \App\Core\AppConfig::debug('LOG_TO_PROFILE');
		\App\Log\Log::$logToConsole = \App\Core\AppConfig::debug('LOG_TO_CONSOLE');
		\App\Log\Log::$logToFile = \App\Core\AppConfig::debug('LOG_TO_FILE');
	}

	/**
	 * Extract service name from command line arguments
	 * @return string|null
	 */
	public static function getServiceNameFromArgs(): ?string
	{
		if (!isset($_SERVER['argv'])) {
			return null;
		}

		foreach ($_SERVER['argv'] as $arg) {
			if (strpos($arg, 'service=') === 0) {
				return substr($arg, 8);
			}
		}

		return null;
	}

	/**
	 * Check if user is authenticated for web access
	 * @return bool
	 */
	public static function isWebUserAuthenticated(): bool
	{
		\App\Http\Vtiger_Session::init();
		
		$authenticatedUserId = \App\Http\Vtiger_Session::get('authenticated_user_id');
		$appUniqueKey = \App\Http\Vtiger_Session::get('app_unique_key');
		$expectedKey = \App\Core\AppConfig::main('application_unique_key');
		
		// Check session authentication
		if (!empty($authenticatedUserId) && !empty($appUniqueKey) && $appUniqueKey === $expectedKey) {
			return true;
		}
		
		return false;
	}

	/**
	 * Write cron execution metadata
	 * @return void
	 */
	public static function writeCronMetadata(): void
	{
		$metadata = sprintf(
			'<?php $sapi=\'%s\';$ini=\'%s\';$log=\'%s\';$vphp=\'%s\';',
			PHP_SAPI,
			php_ini_loaded_file(),
			ini_get('error_log'),
			PHP_VERSION
		);
		
		file_put_contents('user_privileges/cron.php', $metadata);
	}
}

