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

namespace FreeCRM\EntryPoint;

use FreeCRM\AppConfig;
use FreeCRM\CRMEntity;
use FreeCRM\Vtiger_Loader;
use App\Debugger;
use FreeCRM\Http\Vtiger_Request;
use FreeCRM\Http\Vtiger_Session;
use FreeCRM\Runtime\Vtiger_Controller;
use FreeCRM\Runtime\Vtiger_Language_Handler;
use App\Cache;
use App\Db;
use App\Log;
use App\RequestUtil;
use App\User;



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
	 * 
	 * This method handles service initialization including:
	 * - Error reporting configuration
	 * - Cache and debugger initialization
	 * - Error handler registration
	 * 
	 * Note: Autoloaders are already loaded in bootstrap section above
	 * 
	 * @return void
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
	 * 
	 * @return void
	 */
	private static function configureErrorReporting()
	{
		error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
		ini_set('display_errors', '1');
		ini_set('log_errors', '1');
	}

	/**
	 * Initialize application services (cache, debugger, database, logging)
	 * 
	 * @return void
	 */
	private static function initializeServices()
	{
		Debugger::init();
		Cache::init();
		// \App\LanguageTranslator::init();

		Db::$connectCache = AppConfig::performance('ENABLE_CACHING_DB_CONNECTION');
		Log::$logToProfile = AppConfig::debug('LOG_TO_PROFILE');
		Log::$logToConsole = AppConfig::debug('LOG_TO_CONSOLE');
		Log::$logToFile = AppConfig::debug('LOG_TO_FILE');
	}

	/**
	 * Register custom error handler if configured
	 * 
	 * @return void
	 */
	private static function registerErrorHandler()
	{
		if (AppConfig::debug('EXCEPTION_ERROR_HANDLER')) {
			require_once __DIR__ . '/WebUI_ErrorHandler.php';
			WebUI_ErrorHandler::register();
		}
	}

	/**
	 * Check if the user has logged in and redirect if necessary
	 * 
	 * @param Vtiger_Request $request
	 * @throws \Exception\AppException When login is required but not present
	 */
	protected function checkLogin(Vtiger_Request $request)
	{
		if (!$this->hasLogin()) {
			$this->handleLoginRedirect();
			throw new \Exception\AppException('Login is required');
		}
	}

	/**
	 * Handle login redirect with return parameters
	 * 
	 * @return void
	 */
	private function handleLoginRedirect()
	{
		$returnParams = isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : '';

		if ($returnParams && !Vtiger_Session::get('return_params')) {
			$returnParams = urlencode($returnParams);
			Vtiger_Session::set('return_params', $returnParams);
		}

		header('Location: index.php');
	}

	/**
	 * Get the instance of the logged in User
	 * 
	 * @return \Users|false User object or false if not logged in
	 */
	public function getLogin()
	{
		$user = parent::getLogin();

		if (!$user && Vtiger_Session::has('authenticated_user_id')) {
			$user = $this->restoreUserFromSession();
		}

		return $user;
	}

	/**
	 * Restore user from session data
	 * 
	 * @return \Users|false User object or false if restoration fails
	 */
	private function restoreUserFromSession()
	{
		$userid = Vtiger_Session::get('authenticated_user_id');
		$appKey = Vtiger_Session::get('app_unique_key');

		if (!$userid || AppConfig::main('application_unique_key') !== $appKey) {
			return false;
		}

		User::getCurrentUserModel();
		$user = CRMEntity::getInstance('Users');
		$user->retrieveCurrentUserInfoFromFile($userid);
		$this->setLogin($user);

		return $user;
	}

	/**
	 * Trigger permission check for the current request
	 * 
	 * @param Vtiger_Controller $handler
	 * @param Vtiger_Request $request
	 * @throws \Exception\AppException When module is not found
	 * @throws \Exception\NoPermitted When user lacks permissions
	 */
	protected function triggerCheckPermission(Vtiger_Controller $handler, Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$moduleModel = \FreeCRM\Modules\Vtiger\Models\Module::getInstance($moduleName);

		if (empty($moduleModel)) {
			$message = \FreeCRM\Runtime\Vtiger_Language_Handler::translate($moduleName) . ' ' . \FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_HANDLER_NOT_FOUND');
			throw new \Exception\AppException($message);
		}

		$userPrivilegesModel = \FreeCRM\Modules\Users\Models\Privileges::getCurrentUserPrivilegesModel();
		$hasPermission = $userPrivilegesModel->hasModulePermission($moduleModel->getId());

		if (!$hasPermission) {
			throw new \Exception\NoPermitted(\FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_NOT_ACCESSIBLE'));
		}

		$handler->checkPermission($request);
	}

	/**
	 * Trigger pre-process phase
	 * 
	 * @param Vtiger_Controller $handler
	 * @param Vtiger_Request $request
	 * @return bool|null True for AJAX requests, null otherwise

	 */
	protected function triggerPreProcess($handler, Vtiger_Request $request)
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
	 * 
	 * @param Vtiger_Controller $handler
	 * @param Vtiger_Request $request
	 * @return bool|null True for AJAX requests, null otherwise
	 */
	protected function triggerPostProcess($handler, Vtiger_Request $request)
	{
		if ($request->isAjax()) {
			return true;
		}

		/** @phpstan-ignore-next-line */
		$handler->postProcess($request);
		return null;
	}

	/**
	 * Check if the application is installed
	 * 
	 * @return bool
	 */
	public function isInstalled()
	{
		$dbconfig = AppConfig::main('dbconfig');
		return !(empty($dbconfig) || empty($dbconfig['db_name']) || $dbconfig['db_name'] === '_DBC_TYPE_');
	}

	/**
	 * Main process method - handles the complete request lifecycle
	 * 
	 * @param Vtiger_Request $request
	 */
	public function process(Vtiger_Request $request)
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
	 * 
	 * @param Vtiger_Request $request
	 */
	private function enforceSecurityPolicies(Vtiger_Request $request)
	{
		$this->enforceSSL();
		$this->checkInstallation();
		$this->enforceUrlRedirect();
	}

	/**
	 * Enforce SSL if configured
	 * 
	 * @return void
	 */
	private function enforceSSL()
	{
		if (!AppConfig::main('forceSSL')) {
			return;
		}

		$browserInfo = RequestUtil::getBrowserInfo();

		if ($browserInfo && is_object($browserInfo) && !$browserInfo->https) {
			$host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
			$uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
			header(sprintf('Location: https://%s%s', $host, $uri), true, 301);
			exit;
		}
	}

	/**
	 * Check if application is installed, redirect if not
	 * 
	 * @return void
	 */
	private function checkInstallation()
	{
		if (!$this->isInstalled()) {
			header('Location: install/Install.php');
			exit;
		}
	}

	/**
	 * Enforce URL redirect if configured
	 * 
	 * @return void
	 */
	private function enforceUrlRedirect()
	{
		if (!AppConfig::main('forceRedirect')) {
			return;
		}

		$browserInfo = RequestUtil::getBrowserInfo();

		if (!$browserInfo || !is_object($browserInfo)) {
			return;
		}

		$protocol = $browserInfo->https ? 'https' : 'http';
		$host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
		$uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
		$requestUrl = "{$protocol}://{$host}{$uri}";
		$siteUrl = AppConfig::main('site_URL');

		if (stripos($requestUrl, $siteUrl) !== 0) {
			header('Location: ' . $siteUrl, true, 301);
			exit;
		}
	}

	/**
	 * Initialize session and CSRF protection
	 * 
	 * @param Vtiger_Request $request
	 */
	private function initializeSession(Vtiger_Request $request)
	{
		Vtiger_Session::init();

		// Initialize CSRF protection if enabled
		if ($this->shouldEnableCsrfProtection($request)) {
			require_once 'config/csrf_config.php';
			require_once 'libraries/csrf-magic/csrf-magic.php';
		}
	}

	/**
	 * Determine if CSRF protection should be enabled
	 * 
	 * @param Vtiger_Request $request
	 * @return bool
	 */
	private function shouldEnableCsrfProtection(Vtiger_Request $request)
	{
		return AppConfig::main('csrfProtection')
			&& $request->get('mode') !== 'reset'
			&& $request->get('action') !== 'Login'
			&& AppConfig::main('systemMode') !== 'demo';
	}

	/**
	 * Initialize global variables and language
	 * 
	 * @param Vtiger_Request $request
	 * @return \Users|false Current user or false
	 */
	private function initializeGlobals(Vtiger_Request $request)
	{
		$currentUser = $this->getLogin();
		vglobal('current_user', $currentUser);

		$currentLanguage = Vtiger_Language_Handler::getLanguage();
		vglobal('current_language', $currentLanguage);

		if ($currentUser) {
			$this->loadLanguageStrings($request, $currentLanguage);
		}

		return $currentUser;
	}

	/**
	 * Load module and application language strings
	 * 
	 * @param Vtiger_Request $request
	 * @param string $currentLanguage
	 */
	private function loadLanguageStrings(Vtiger_Request $request, $currentLanguage)
	{
		$qualifiedModuleName = $request->getModule(false);

		if ($qualifiedModuleName) {
			$this->loadModuleLanguageStrings($qualifiedModuleName, $currentLanguage);
		}

		$this->loadAppLanguageStrings($currentLanguage);
	}

	/**
	 * Load module-specific language strings
	 * 
	 * @param string $moduleName
	 * @param string $language
	 */
	private function loadModuleLanguageStrings($moduleName, $language)
	{
		$moduleLanguageStrings = Vtiger_Language_Handler::getModuleStringsFromFile($language, $moduleName);

		if (isset($moduleLanguageStrings['languageStrings'])) {
			vglobal('mod_strings', $moduleLanguageStrings['languageStrings']);
		}
	}

	/**
	 * Load application-wide language strings
	 * 
	 * @param string $language
	 */
	private function loadAppLanguageStrings($language)
	{
		$moduleLanguageStrings = Vtiger_Language_Handler::getModuleStringsFromFile($language);

		if (isset($moduleLanguageStrings['languageStrings'])) {
			vglobal('app_strings', $moduleLanguageStrings['languageStrings']);
		}
	}

	/**
	 * Handle the main request processing
	 * 
	 * @param Vtiger_Request $request
	 * @param \Users|false $currentUser
	 * @return mixed Response object or false
	 */
	private function handleRequest(Vtiger_Request $request, $currentUser)
	{
		$this->resolveModuleAndView($request);

		$module = $request->getModule();
		$qualifiedModuleName = $request->getModule(false);

		$this->checkSettingsAccess($qualifiedModuleName, $currentUser);

		list($componentType, $componentName) = $this->resolveComponent($request);

		define('_PROCESS_TYPE', $componentType);
		define('_PROCESS_NAME', $componentName);

		$handler = $this->createHandler($componentType, $componentName, $qualifiedModuleName);

		return $this->executeHandler($handler, $request, $module, $qualifiedModuleName);
	}

	/**
	 * Resolve module and view from request, setting defaults if needed
	 * 
	 * @param Vtiger_Request $request
	 */
	private function resolveModuleAndView(Vtiger_Request $request)
	{
		$module = $request->get('module');

		if (empty($module)) {
			$this->setDefaultModuleAndView($request);
		}
	}

	/**
	 * Set default module and view based on login status
	 * 
	 * @param Vtiger_Request $request
	 */
	private function setDefaultModuleAndView(Vtiger_Request $request)
	{
		if ($this->hasLogin()) {
			$this->setDefaultModuleForLoggedInUser($request);
		} else {
			$this->setLoginModule($request);
		}
	}

	/**
	 * Set default module for logged in user
	 * 
	 * @param Vtiger_Request $request
	 */
	private function setDefaultModuleForLoggedInUser(Vtiger_Request $request)
	{
		$defaultModule = AppConfig::main('default_module');

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
	 * 
	 * @param Vtiger_Request $request
	 */
	private function setLoginModule(Vtiger_Request $request)
	{
		$request->set('module', self::USERS_MODULE);
		$request->set('view', self::LOGIN_VIEW);
	}

	/**
	 * Check if user has access to settings pages
	 * 
	 * @param string|null $qualifiedModuleName
	 * @param \Users|false $currentUser
	 */
	private function checkSettingsAccess($qualifiedModuleName, $currentUser)
	{
		if (
			$qualifiedModuleName
			&& stripos($qualifiedModuleName, 'Settings') === 0
			&& empty($currentUser)
		) {
			header('Location: ' . AppConfig::main('site_URL'), true);
			exit;
		}
	}

	/**
	 * Resolve component type and name from request
	 * 
	 * @param Vtiger_Request $request
	 * @return array [componentType, componentName]
	 */
	private function resolveComponent(Vtiger_Request $request)
	{
		$action = $request->get('action');

		if (!empty($action)) {
			return ['Action', $action];
		}

		$view = $request->get('view');
		$componentName = empty($view) ? 'Index' : $view;

		return ['View', $componentName];
	}

	/**
	 * Create handler instance
	 * 
	 * @param string $componentType
	 * @param string $componentName
	 * @param string|null $qualifiedModuleName
	 * @return Vtiger_Controller
	 * @throws \Exception\AppException When handler class not found
	 */
	private function createHandler($componentType, $componentName, $qualifiedModuleName)
	{
		// Use new PSR-4 loader for modern modules
		$handlerClass = \FreeCRM\Loader::getComponentClassName(
			$componentType,
			$componentName,
			$qualifiedModuleName
		);

		$handler = new $handlerClass();

		if (!$handler) {
			throw new \Exception\AppException(\FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_HANDLER_NOT_FOUND'));
		}

		return $handler;
	}

	/**
	 * Execute handler with full lifecycle
	 * 
	 * @param Vtiger_Controller $handler
	 * @param Vtiger_Request $request
	 * @param string $module
	 * @param string|null $qualifiedModuleName
	 * @return mixed Response object or false
	 */
	private function executeHandler($handler, Vtiger_Request $request, $module, $qualifiedModuleName)
	{
		vglobal('currentModule', $module);

		$this->validateHandler($handler, $request);
		$this->checkHandlerLogin($handler, $request);
		$this->checkHandlerPermissions($handler, $request, $module, $qualifiedModuleName);
		$this->handleNotPermittedListModules($module, $request);

		$this->triggerPreProcess($handler, $request);
		/** @phpstan-ignore-next-line */
		$response = $handler->process($request);
		$this->triggerPostProcess($handler, $request);

		return $response;
	}

	/**
	 * Validate handler request
	 * 
	 * @param Vtiger_Controller $handler
	 * @param Vtiger_Request $request
	 */
	private function validateHandler($handler, Vtiger_Request $request)
	{
		if (AppConfig::main('csrfProtection') && AppConfig::main('systemMode') !== 'demo') {
			/** @phpstan-ignore-next-line */
			$handler->validateRequest($request);
		}
	}

	/**
	 * Check if handler requires login
	 * 
	 * @param Vtiger_Controller $handler
	 * @param Vtiger_Request $request
	 */
	private function checkHandlerLogin($handler, Vtiger_Request $request)
	{
		if ($handler->loginRequired()) {
			$this->checkLogin($request);
		}
	}

	/**
	 * Check handler permissions
	 * 
	 * @param Vtiger_Controller $handler
	 * @param Vtiger_Request $request
	 * @param string $module
	 * @param string|null $qualifiedModuleName
	 */
	private function checkHandlerPermissions($handler, Vtiger_Request $request, $module, $qualifiedModuleName)
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
	 * 
	 * @param string $module
	 * @param Vtiger_Request $request
	 */
	private function handleNotPermittedListModules($module, Vtiger_Request $request)
	{
		$view = $request->get('view');

		if (in_array($module, self::NOT_PERMITTED_LIST_MODULES, true) && $view === self::LIST_VIEW) {
			header('Location: index.php?module=Home&view=DashBoard');
			exit;
		}
	}

	/**
	 * Handle exceptions during request processing
	 * 
	 * @param \Exception $exception
	 * @param Vtiger_Request $request
	 * @throws \Exception In test mode
	 */
	private function handleException(\Exception $exception, Vtiger_Request $request)
	{
		Log::error(
			$exception->getMessage() . ' => ' .
			$exception->getFile() . ':' .
			$exception->getLine()
		);

		$template = $this->getExceptionTemplate($exception);
		\vtlib\Functions::throwNewException($exception, false, $template);

		if (AppConfig::debug('DISPLAY_DEBUG_BACKTRACE') && !$request->isAjax()) {
			$this->displayDebugBacktrace($exception);
		}

		if (AppConfig::main('systemMode') === 'test') {
			$this->logRequestForTesting($request);
			throw $exception;
		}
	}

	/**
	 * Get appropriate exception template
	 * 
	 * @param \Exception $exception
	 * @return string
	 */
	private function getExceptionTemplate(\Exception $exception)
	{
		if (
			$exception instanceof \Exception\NoPermittedToRecord
			|| $exception instanceof \WebServiceException
		) {
			return 'NoPermissionsForRecord.tpl';
		}

		return 'OperationNotPermitted.tpl';
	}

	/**
	 * Display debug backtrace
	 * 
	 * @param \Exception $exception
	 */
	private function displayDebugBacktrace(\Exception $exception)
	{
		$trace = str_replace(
			ROOT_DIRECTORY . DIRECTORY_SEPARATOR,
			'',
			$exception->getTraceAsString()
		);
		echo '<pre>' . $trace . '</pre>';
	}

	/**
	 * Log request data for testing purposes
	 * 
	 * @param Vtiger_Request $request
	 */
	private function logRequestForTesting(Vtiger_Request $request)
	{
		file_put_contents(
			'cache/logs/request.log',
			print_r($request->getAll(), true)
		);
	}
}

