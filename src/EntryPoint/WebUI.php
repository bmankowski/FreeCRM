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

/**
 * WebUI Entry Point
 * 
 * Handles all web-based user interface requests, including authentication,
 * routing, permission checking, and request lifecycle management.
 * 
 * @package Main
 */

namespace App\EntryPoint;


use App\Base\Controllers\BaseActionController;
use App\Cache\Cache;


class WebUI extends EntryPoint
{
	/** @var bool Flag to track if initialization has occurred */
	private static $initialized = false;

	// Default modules and views
	const DEFAULT_MODULE = 'Home';
	const DEFAULT_VIEW = 'DashBoard';
	const USERS_MODULE = 'Users';
	const LOGIN_VIEW = 'Login';
	const CALENDAR_MODULE = 'Calendar';
	const CALENDAR_VIEW = 'Calendar';
	const LIST_VIEW = 'List';

	// Modules that skip standard permission checks
	const SKIP_PERMISSION_MODULES = [
		'Users',
		'Home',
		'CustomView',
		'Import',
		'Export',
		'Inventory',
		'Vtiger',
		'Migration',
		'Install',
		'ModTracker',
		'WSAPP',
	];

	// Modules that should not have list views
	const NOT_PERMITTED_LIST_MODULES = [
		'ModComments',
		'Integration',
		'DashBoard',
	];

	/**
	 * Initialize the WebUI environment
	 * 	 * This method handles service initialization including:
	 * - Error reporting configuration
	 * - Cache and debugger initialization
	 * - Error handler registration
	 * 	 * Note: Autoloaders are already loaded in bootstrap section above
	 * 	 * @return void
	 */
	public static function initialize()
	{
		if (self::$initialized) {
			return;
		}

		self::configureErrorReporting();
		self::initializeServices();
		self::registerErrorHandler();

		self::$initialized = true;
	}

	/**
	 * Configure PHP error reporting for production
	 * 	 * @return void
	 */
	private static function configureErrorReporting()
	{
		error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
		ini_set('display_errors', '1');
		ini_set('log_errors', '1');
		ini_set('html_errors', '0');  // Disable HTML formatting in error logs
	}

	/**
	 * Initialize application services (cache, debugger, database, logging)
	 * 	 * @return void
	 */
	private static function initializeServices()
	{
		\App\Debug\Debugger::init();
		Cache::init();

		\App\Db\Db::$connectCache = \App\Core\AppConfig::performance('ENABLE_CACHING_DB_CONNECTION');
		\App\Log\Log::$logToProfile = \App\Core\AppConfig::debug('LOG_TO_PROFILE');
		\App\Log\Log::$logToConsole = \App\Core\AppConfig::debug('LOG_TO_CONSOLE');
		\App\Log\Log::$logToFile = \App\Core\AppConfig::debug('LOG_TO_FILE');
	}

	/**
	 * Register custom error handler if configured
	 * 	 * @return void
	 */
	private static function registerErrorHandler()
	{
		if (\App\Core\AppConfig::debug('EXCEPTION_ERROR_HANDLER')) {
			require_once __DIR__ . '/WebUI_ErrorHandler.php';
			WebUI_ErrorHandler::register();
		}
	}

	/**
	 * Check if the user has logged in and redirect if necessary
	 * 	 * @param \App\Http\Vtiger_Request $request
	 * @throws \App\Exceptions\AppException When login is required but not present
	 */
	protected function checkLogin(\App\Http\Vtiger_Request $request)
	{
		if (!$this->hasLogin()) {
			$this->handleLoginRedirect();
			throw new \App\Exceptions\AppException('Login is required');
		}
	}

	/**
	 * Handle login redirect with return parameters
	 * 	 * @return void
	 */
	private function handleLoginRedirect()
	{
		$returnParams = isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : '';

		if ($returnParams && !\App\Http\Vtiger_Session::get('return_params')) {
			$returnParams = urlencode($returnParams);
			\App\Http\Vtiger_Session::set('return_params', $returnParams);
		}

		header('Location: index.php');
	}

	/**
	 * Get the instance of the logged in User
	 * 	 * @return \Users|false User object or null if not logged in
	 */
	public function getLogin()
	{
		$user = parent::getLogin();

		if (!$user) {
			$userid = \App\Http\Vtiger_Session::getEffectiveUserId();

			if ($userid) {
				$user = \App\Core\CRMEntity::getInstance('Users');
				$user->retrieveCurrentUserInfoFromFile($userid);
				$this->setLogin($user);
				// One-shot rebuild of privilege files after large data imports.
				// This prevents list views from breaking when records reference imported user IDs.
				try {
					if ($user->is_admin === 'on') {
						$flag = ROOT_DIRECTORY . '/cache/.user_privileges_rebuilt';
						if (!file_exists($flag)) {
							\App\Utils\VtlibUtils::recreateUserPrivilegeFiles();
							@file_put_contents($flag, (string) time());
						}
					}
				} catch (\Throwable $e) {
					// Best-effort only.
				}
			}
		}
		return $user;
	}

	/**
	 * Trigger permission check for the current request
	 * 	 * @param \App\Base\Controllers\BaseActionController $handler
	 * @param \App\Http\Vtiger_Request $request
	 * @throws \App\Exceptions\AppException When module is not found
	 * @throws \App\Exceptions\NoPermitted When user lacks permissions
	 */
	protected function triggerCheckPermission(BaseActionController $handler, \App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$moduleModel = \App\Modules\Base\Models\Module::getInstance($moduleName);

		if (empty($moduleModel)) {
			$message = \App\Runtime\Vtiger_Language_Handler::translate($moduleName) . ' ' . \App\Runtime\Vtiger_Language_Handler::translate('LBL_HANDLER_NOT_FOUND');
			throw new \App\Exceptions\AppException($message);
		}

		$userPrivilegesModel = \App\Modules\Users\Models\Privileges::getCurrentUserPrivilegesModel();
		$hasPermission = $userPrivilegesModel->hasModulePermission($moduleModel->getId());

		if (!$hasPermission) {
			throw new \App\Exceptions\NoPermitted(\App\Runtime\Vtiger_Language_Handler::translate('LBL_NOT_ACCESSIBLE'));
		}

		$handler->checkPermission($request);
	}

	/**
	 * Trigger pre-process phase
	 * 	 * @param \App\Base\Controllers\BaseActionController $handler
	 * @param \App\Http\Vtiger_Request $request
	 * @return bool|null True for AJAX requests, null otherwise

	 */
	protected function triggerPreProcess($handler, \App\Http\Vtiger_Request $request)
	{
		if ($request->isAjax()) {
			$handler->preProcessAjax($request);
			return true;
		}

		$handler->preProcess($request);
		return null;
	}

	/**
	 * Trigger post-process phase
	 * 	 * @param \App\Base\Controllers\BaseActionController $handler
	 * @param \App\Http\Vtiger_Request $request
	 * @return bool|null True for AJAX requests, null otherwise
	 */
	protected function triggerPostProcess($handler, \App\Http\Vtiger_Request $request)
	{
		if ($request->isAjax()) {
			return true;
		}
		return $handler->postProcess($request);
	}

	/**
	 * Check if the application is installed
	 * 	 * @return bool
	 */
	public function isInstalled()
	{
		$dbconfig = \App\Core\AppConfig::main('dbconfig');
		if (empty($dbconfig) || empty($dbconfig['db_name']) || $dbconfig['db_name'] === '_DBC_TYPE_') {
			return false;
		}
		try {
			$db = \App\Db\Db::getInstance('base');
			$db->open();
			// Consider the app installed only when a user exists in vtiger_users.
			// This avoids treating a partially-imported schema (table exists but empty) as installed.
			return (bool) $db->createCommand('SELECT 1 FROM vtiger_users LIMIT 1')->queryScalar();
		} catch (\Throwable $e) {
			// If DB is not reachable or schema missing, treat as not installed.
			return false;
		}
	}

	/**
	 * Main process method - handles the complete request lifecycle
	 * 	 * @param \App\Http\Vtiger_Request $request
	 */
	public function process(\App\Http\Vtiger_Request $request)
	{
		$this->enforceSecurityPolicies($request);
		$this->initializeSession($request);

		$currentUser = $this->initializeGlobals($request);

		$response = false;

		try {
			$response = $this->handleRequest($request, $currentUser);
		} catch (\Exception $exception) {
			$this->handleException($exception, $request);
		}

		if ($response) {
			$response->emit();
		}
	}

	/**
	 * Enforce security policies (SSL, installation check, URL redirect)
	 * 	 * @param \App\Http\Vtiger_Request $request
	 */
	private function enforceSecurityPolicies(\App\Http\Vtiger_Request $request)
	{
		$this->enforceSSL();
		$this->checkInstallation();
		$this->enforceUrlRedirect();
	}

	/**
	 * Enforce SSL if configured
	 * 	 * @return void
	 */
	private function enforceSSL()
	{
		if (!\App\Core\AppConfig::main('forceSSL')) {
			return;
		}

		$browserInfo = \App\Utils\RequestUtil::getBrowserInfo();

		if ($browserInfo && is_object($browserInfo) && !$browserInfo->https) {
			$host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
			$uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
			header(sprintf('Location: https://%s%s', $host, $uri), true, 301);
			exit;
		}
	}

	/**
	 * Check if application is installed, redirect if not
	 * 	 * @return void
	 */
	private function checkInstallation()
	{
		// Avoid redirect loops when the installer module is already being served.
		if (isset($_GET['module']) && $_GET['module'] === 'Install') {
			return;
		}
		if (!$this->isInstalled()) {
			// Use the main front controller for the installer module.
			// This project layout keeps installer code under src/Modules/Install/.
			header('Location: index.php?module=Install&view=Index');
			exit;
		}
	}

	/**
	 * Enforce URL redirect if configured
	 * 	 * @return void
	 */
	private function enforceUrlRedirect()
	{
		if (!\App\Core\AppConfig::main('forceRedirect')) {
			return;
		}

		$browserInfo = \App\Utils\RequestUtil::getBrowserInfo();

		if (!$browserInfo || !is_object($browserInfo)) {
			return;
		}

		$protocol = $browserInfo->https ? 'https' : 'http';
		$host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
		$uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
		$requestUrl = "{$protocol}://{$host}{$uri}";
		$siteUrl = \App\Core\AppConfig::main('site_URL');

		if (stripos($requestUrl, $siteUrl) !== 0) {
			header('Location: ' . $siteUrl, true, 301);
			exit;
		}
	}

	/**
	 * Initialize session and CSRF protection
	 * 	 * @param \App\Http\Vtiger_Request $request
	 */
	private function initializeSession(\App\Http\Vtiger_Request $request)
	{
		\App\Http\Vtiger_Session::init();

		// Initialize CSRF protection if enabled
		if ($this->shouldEnableCsrfProtection($request)) {
			require_once 'config/csrf_config.php';
			require_once 'libraries/csrf-magic/csrf-magic.php';
		}
	}

	/**
	 * Determine if CSRF protection should be enabled
	 * 	 * @param \App\Http\Vtiger_Request $request
	 * @return bool
	 */
	private function shouldEnableCsrfProtection(\App\Http\Vtiger_Request $request)
	{
		return \App\Core\AppConfig::main('csrfProtection')
			&& $request->get('mode') !== 'reset'
			&& $request->get('action') !== 'Login'
			&& \App\Core\AppConfig::main('systemMode') !== 'demo';
	}

	/**
	 * Initialize global variables and language
	 * UPDATED: Attach user to request and session
	 * 	 * @param \App\Http\Vtiger_Request $request
	 * @return \Users|false Current user or false
	 */
	private function initializeGlobals(\App\Http\Vtiger_Request $request)
	{
		$currentUser = $this->getLogin();

		// Attach user to request if authenticated
		if ($currentUser && $currentUser->id) {
			$userModel = \App\Modules\Users\Models\Record::getInstanceById($currentUser->id, 'Users');
			$request->setUser($userModel);
		}
		if ($currentUser) {
			$currentLanguage = \App\Runtime\Vtiger_Language_Handler::getLanguage();
			$this->loadLanguageStrings($request, $currentLanguage);
		}

		return $currentUser;
	}

	/**
	 * Load module and application language strings
	 * 	 * @param \App\Http\Vtiger_Request $request
	 * @param string $currentLanguage
	 */
	private function loadLanguageStrings(\App\Http\Vtiger_Request $request, $currentLanguage)
	{
		$qualifiedModuleName = $request->getModule(false);

		if ($qualifiedModuleName) {
			$this->loadModuleLanguageStrings($qualifiedModuleName, $currentLanguage);
		}

		$this->loadAppLanguageStrings($currentLanguage);
	}

	/**
	 * Load module-specific language strings
	 * 	 * @param string $moduleName
	 * @param string $language
	 */
	private function loadModuleLanguageStrings($moduleName, $language)
	{
		$moduleLanguageStrings = \App\Runtime\Vtiger_Language_Handler::getModuleStringsFromFile($language, $moduleName);
	}

	/**
	 * Load application-wide language strings
	 * 	 * @param string $language
	 */
	private function loadAppLanguageStrings($language)
	{
		$moduleLanguageStrings = \App\Runtime\Vtiger_Language_Handler::getModuleStringsFromFile($language);
	}

	/**
	 * Handle the main request processing
	 * 	 * @param \App\Http\Vtiger_Request $request
	 * @param \Users|false $currentUser
	 * @return mixed Response object or false
	 */
	private function handleRequest(\App\Http\Vtiger_Request $request, $currentUser)
	{
		$rawGet = $_GET ?? [];
		$this->resolveModuleAndView($request);
		$this->logHttpRequest($request, $rawGet);

		$module = $request->getModule();
		$qualifiedModuleName = $request->getModule(false);

		$this->checkSettingsAccess($qualifiedModuleName, $request, $currentUser);

		list($componentType, $componentName) = $this->resolveComponent($request, $qualifiedModuleName);

		define('_PROCESS_TYPE', $componentType);
		define('_PROCESS_NAME', $componentName);

		$handler = $this->createHandler($componentType, $componentName, $qualifiedModuleName);

		return $this->executeHandler($handler, $request, $module, $qualifiedModuleName);
	}

	/**
	 * Resolve module and view from request, setting defaults if needed
	 * 	 * @param \App\Http\Vtiger_Request $request
	 */
	private function resolveModuleAndView(\App\Http\Vtiger_Request $request)
	{
		$module = $request->get('module');

		if (empty($module)) {
			$this->setDefaultModuleAndView($request);
		}
	}

	/**
	 * Set default module and view based on login status
	 * 	 * @param \App\Http\Vtiger_Request $request
	 */
	private function setDefaultModuleAndView(\App\Http\Vtiger_Request $request)
	{
		if ($this->hasLogin()) {
			$this->setDefaultModuleForLoggedInUser($request);
		} else {
			$this->setLoginModule($request);
		}
	}

	/**
	 * Set default module for logged in user
	 * 	 * @param \App\Http\Vtiger_Request $request
	 */
	private function setDefaultModuleForLoggedInUser(\App\Http\Vtiger_Request $request)
	{
		$defaultModule = \App\Core\AppConfig::main('default_module');

		if (!empty($defaultModule) && $defaultModule !== self::DEFAULT_MODULE) {
			$module = $defaultModule;
			$view = ($module === self::CALENDAR_MODULE)
				? self::CALENDAR_VIEW
				: self::LIST_VIEW;
		} else {
			$module = self::DEFAULT_MODULE;
			$view = self::DEFAULT_VIEW;
		}

		$request->set('module', $module);
		$request->set('view', $view);
	}

	/**
	 * Set login module for non-authenticated users
	 * 	 * @param \App\Http\Vtiger_Request $request
	 */
	private function setLoginModule(\App\Http\Vtiger_Request $request)
	{
		$request->set('module', self::USERS_MODULE);
		$request->set('view', self::LOGIN_VIEW);
	}

	/**
	 * Check if user has access to settings pages
	 * 	 * @param string|null $qualifiedModuleName
	 * @param \App\Http\Vtiger_Request $request
	 * @param User|false $currentUser
	 */
	private function checkSettingsAccess($qualifiedModuleName, \App\Http\Vtiger_Request $request, $currentUser)
	{
		if (
			$qualifiedModuleName
			&& stripos($qualifiedModuleName, 'Settings') === 0
			&& (empty($currentUser) || !$request->isUserAdmin())
		) {
			header('Location: ' . \App\Core\AppConfig::main('site_URL'), true);
			exit;
		}
	}

	/**
	 * Resolve component type and name from request
	 * 	 * @param \App\Http\Vtiger_Request $request
	 * @param string $moduleName Module name
	 * @return array [componentType, componentName]
	 */
	private function resolveComponent(\App\Http\Vtiger_Request $request, $moduleName)
	{
		$action = $request->get('action');

		if (!empty($action)) {
			return ['Action', $action];
		}

		$view = $request->get('view');
		
		// If no view specified, use module's default view
		if (empty($view)) {
			$moduleModel = \App\Modules\Base\Models\Module::getInstance($moduleName);
			if ($moduleModel && method_exists($moduleModel, 'getDefaultViewName')) {
				$componentName = $moduleModel->getDefaultViewName();
			} else {
				$componentName = 'Index';
			}
		} else {
			$componentName = $view;
		}

		return ['View', $componentName];
	}

	/**
	 * Create handler instance
	 * 	 * @param string $componentType
	 * @param string $componentName
	 * @param string|null $qualifiedModuleName
	 * @return \App\Base\Controllers\BaseActionController
	 * @throws \App\Exceptions\AppException When handler class not found
	 */
	private function createHandler($componentType, $componentName, $qualifiedModuleName)
	{
		// Use new PSR-4 loader for modern modules
		$handlerClass = \App\Core\Loader::getComponentClassName(
			$componentType,
			$componentName,
			$qualifiedModuleName
		);

		// Validate that the class exists and can be instantiated
		if (empty($handlerClass) || !class_exists($handlerClass)) {
			\App\Log\Log::error("Handler class not found: $handlerClass for $componentType/$componentName in $qualifiedModuleName");
			throw new \App\Exceptions\AppException(\App\Runtime\Vtiger_Language_Handler::translate('LBL_HANDLER_NOT_FOUND'));
		}

		try {
			$handler = new $handlerClass();
		} catch (\Throwable $e) {
			\App\Log\Log::error("Failed to instantiate handler class $handlerClass: " . $e->getMessage());
			throw new \App\Exceptions\AppException(\App\Runtime\Vtiger_Language_Handler::translate('LBL_HANDLER_NOT_FOUND'));
		}

		if (!$handler) {
			throw new \App\Exceptions\AppException(\App\Runtime\Vtiger_Language_Handler::translate('LBL_HANDLER_NOT_FOUND'));
		}

		return $handler;
	}

	/**
	 * Execute handler with full lifecycle
	 * 	 * @param \App\Base\Controllers\BaseActionController $handler
	 * @param \App\Http\Vtiger_Request $request
	 * @param string $module
	 * @param string|null $qualifiedModuleName
	 * @return mixed Response object or false
	 */
	private function executeHandler($handler, \App\Http\Vtiger_Request $request, $module, $qualifiedModuleName)
	{
		$this->validateHandler($handler, $request);
		$this->checkHandlerLogin($handler, $request);
		$this->checkHandlerPermissions($handler, $request, $module, $qualifiedModuleName);
		$this->handleNotPermittedListModules($module, $request);
		$this->triggerPreProcess($handler, $request);
		$response = $handler->process($request);
		$this->triggerPostProcess($handler, $request);

		return $response;
	}

	/**
	 * Validate handler request
	 * 	 * @param \App\Base\Controllers\BaseActionController $handler
	 * @param \App\Http\Vtiger_Request $request
	 */
	private function validateHandler($handler, \App\Http\Vtiger_Request $request)
	{
		if (\App\Core\AppConfig::main('csrfProtection') && \App\Core\AppConfig::main('systemMode') !== 'demo') {

			$handler->validateRequest($request);
		}
	}

	/**
	 * Check if handler requires login
	 * 	 * @param \App\Base\Controllers\BaseActionController $handler
	 * @param \App\Http\Vtiger_Request $request
	 */
	private function checkHandlerLogin($handler, \App\Http\Vtiger_Request $request)
	{
		if ($handler->loginRequired()) {
			$this->checkLogin($request);
		}
	}

	/**
	 * Check handler permissions
	 * 	 * @param \App\Base\Controllers\BaseActionController $handler
	 * @param \App\Http\Vtiger_Request $request
	 * @param string $module
	 * @param string|null $qualifiedModuleName
	 */
	private function checkHandlerPermissions($handler, \App\Http\Vtiger_Request $request, $module, $qualifiedModuleName)
	{
		$isSettingsModule = $qualifiedModuleName && stripos($qualifiedModuleName, 'Settings') === 0;
		$isUsersModule = $module === self::USERS_MODULE;

		// Check standard module permissions
		if (!in_array($module, self::SKIP_PERMISSION_MODULES, true) && !$isSettingsModule) {
			$this->triggerCheckPermission($handler, $request);
		}

		// Settings and Users modules have their own permission checks
		if (($isSettingsModule || $isUsersModule) && method_exists($handler, 'checkPermission')) {

			$handler->checkPermission($request);
		}
	}

	/**
	 * Handle modules that should not have list views
	 * 	 * @param string $module
	 * @param \App\Http\Vtiger_Request $request
	 */
	private function handleNotPermittedListModules($module, \App\Http\Vtiger_Request $request)
	{
		$view = $request->get('view');

		if (in_array($module, self::NOT_PERMITTED_LIST_MODULES, true) && $view === self::LIST_VIEW) {
			header('Location: index.php?module=Home&view=Index');
			exit;
		}
	}

	/**
	 * Handle exceptions during request processing
	 * 	 * @param \Exception $exception
	 * @param \App\Http\Vtiger_Request $request
	 * @throws \Exception In test mode
	 */
	private function handleException(\Exception $exception, \App\Http\Vtiger_Request $request)
	{
		\App\Log\Log::error(
			$exception->getMessage() . ' => ' .
			$exception->getFile() . ':' .
			$exception->getLine()
		);

		if (!$request->isAjax() && $this->renderErrorInLayout($exception, $request)) {
			if (\App\Core\AppConfig::main('systemMode') === 'test') {
				$this->logRequestForTesting($request);
				throw $exception;
			}
			return;
		}

		$template = $this->getExceptionTemplate($exception);
		\vtlib\Functions:: throwNewException($exception, false, $template);

		if (\App\Core\AppConfig::main('systemMode') === 'test') {
			$this->logRequestForTesting($request);
			throw $exception;
		}
	}

	/**
	 * Render the exception inside the full CRM layout (menu, header, footer).
	 * Requires an authenticated user; returns false so the caller falls back to
	 * the standalone error templates when the layout cannot be produced.
	 * @param \Exception $exception
	 * @param \App\Http\Vtiger_Request $request
	 * @return bool True when a complete page was emitted
	 */
	private function renderErrorInLayout(\Exception $exception, \App\Http\Vtiger_Request $request)
	{
		if (headers_sent()) {
			return false;
		}
		$user = $request->getUser();
		if (!$user || !$user->getId()) {
			return false;
		}

		ob_start();
		try {
			$view = new \App\Modules\Base\Views\Error();
			$view->setException($exception);
			$view->preProcess($request);
			$view->process($request);
			$view->postProcess($request);
			echo ob_get_clean();
			return true;
		} catch (\Throwable $e) {
			ob_end_clean();
			\App\Log\Log::error('Error layout rendering failed: ' . $e->getMessage() . ' => ' . $e->getFile() . ':' . $e->getLine());
			return false;
		}
	}

	/**
	 * Get appropriate exception template
	 * 	 * @param \Exception $exception
	 * @return string
	 */
	private function getExceptionTemplate(\Exception $exception)
	{
		if ($exception instanceof \App\Exceptions\NoPermittedToRecord) {
			if (in_array($exception->getMessage(), ['LBL_RECORD_NOT_FOUND', 'LBL_RECORD_DELETE'], true)) {
				return 'RecordNotFound.tpl';
			}
			return 'NoPermissionsForRecord.tpl';
		}
		if ($exception instanceof \WebServiceException) {
			return 'NoPermissionsForRecord.tpl';
		}

		return 'OperationNotPermitted.tpl';
	}

	/**
	 * Log incoming HTTP request (raw GET + resolved routing).
	 */
	private function logHttpRequest(\App\Http\Vtiger_Request $request, array $rawGet): void
	{
		if (!\App\Core\AppConfig::debug('LOG_HTTP_REQUESTS')) {
			return;
		}

		$line = sprintf(
			'%s %s | ajax=%d | raw_get=%s | module=%s | view=%s | action=%s | mode=%s | referer=%s',
			$_SERVER['REQUEST_METHOD'] ?? '?',
			$_SERVER['REQUEST_URI'] ?? '?',
			$request->isAjax() ? 1 : 0,
			json_encode($rawGet, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}',
			$request->getModule() ?: '-',
			$request->get('view') ?: '-',
			$request->get('action') ?: '-',
			$request->get('mode') ?: '-',
			$_SERVER['HTTP_REFERER'] ?? '-'
		);

		file_put_contents(
			ROOT_DIRECTORY . '/cache/logs/http-requests.log',
			date('Y-m-d H:i:s') . ' ' . $line . "\n",
			FILE_APPEND | LOCK_EX
		);
	}

	/**
	 * Log request data for testing purposes
	 * 	 * @param \App\Http\Vtiger_Request $request
	 */
	private function logRequestForTesting(\App\Http\Vtiger_Request $request)
	{
		file_put_contents(
			'cache/logs/request.log',
			print_r($request->getAll(), true)
		);
	}
}

