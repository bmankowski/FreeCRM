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


namespace App\Http;

use App\AppConfig;
use App\Json;
use App\Purifier;
use Exception;

class Vtiger_Request
{

	// Datastore
	protected $valueMap = [];

	protected $rawValueMap = [];

	protected $defaultmap = [];

	protected $headers = [];

	/** @var \App\Modules\Users\Models\Record|null Authenticated user */
	protected $authenticatedUser = null;

	/** @var array Service container for dependency injection */
	protected $services = [];

	/** @var array Request-scoped cache */
	protected $cache = [];

	/**
	 * Default constructor
	 */
	public function __construct($values, $rawvalues = [], $stripifgpc = true)
	{
		$this->rawValueMap = $values;
		// if ($stripifgpc && !empty($this->rawValueMap) && function_exists('get_magic_quotes_gpc') && \get_magic_quotes_gpc()) { //FUNCTION DOES NOT EXIST IN PHP 8.2+ BMN REMOVED it
		if ($stripifgpc && !empty($this->rawValueMap)) {
			$this->rawValueMap = $this->stripslashes_recursive($this->rawValueMap);
		}
	}

	/**
	 * Strip the slashes recursively on the values.
	 */
	public function stripslashes_recursive($value)
	{
		return is_array($value) ? array_map([$this, 'stripslashes_recursive'], $value) : stripslashes($value);
	}

	/**
	 * Get key value (otherwise default value)
	 */
	public function get($key, $defvalue = '')
	{
		$value = $defvalue;
		if (isset($this->valueMap[$key])) {
			return $this->valueMap[$key];
		}

		if (isset($this->rawValueMap[$key])) {
			$value = $this->rawValueMap[$key];
		}

		if ($value === '' && isset($this->defaultmap[$key])) {
			$value = $this->defaultmap[$key];
		}

		$isJSON = false;
		// NOTE: Zend_Json or json_decode gets confused with big-integers (when passed as string)
		// and convert them to ugly exponential format - to overcome this we are performin a pre-check
		if (is_string($value) && (strpos($value, '[') === 0 || strpos($value, '{') === 0)) {
			$isJSON = true;
		}

		if ($isJSON) {
			$decodeValue = Json::decode($value);
			if (isset($decodeValue)) {
				$value = $decodeValue;
			}
		}

		//Handled for null because Purifier::purify returns empty string
		if (!empty($value)) {
			$value = Purifier::purify($value);
		}

		$this->valueMap[$key] = $value;
		return $value;
	}

	/**
	 * Get value for key as boolean
	 */
	public function getBoolean($key, $defvalue = '')
	{
		return strcasecmp('true', $this->get($key, $defvalue) . '') === 0;
	}

	/**
	 * Function to get the value if its safe to use for SQL Query (column).
	 * @param string $key
	 * @param boolean $skipEmpty - Skip the check if string is empty
	 * @return Value for the given key
	 */
	public function getForSql($key, $skipEmtpy = true)
	{
		return Purifier::purifySql($this->get($key), $skipEmtpy);
	}

	public function getForHtml($key, $defvalue = '')
	{
		$value = $defvalue;
		if (isset($this->rawValueMap[$key])) {
			$value = $this->rawValueMap[$key];
		}

		if ($value === '' && isset($this->defaultmap[$key])) {
			$value = $this->defaultmap[$key];
		}

		$isJSON = false;
		// NOTE: Zend_Json or json_decode gets confused with big-integers (when passed as string)
		// and convert them to ugly exponential format - to overcome this we are performin a pre-check
		if (is_string($value) && (strpos($value, "[") === 0 || strpos($value, "{") === 0)) {
			$isJSON = true;
		}

		if ($isJSON) {
			$decodeValue = Json::decode($value);
			if (isset($decodeValue)) {
				$value = $decodeValue;
			}
		}

		//Handled for null because Purifier::purifyHtml returns empty string
		if (!empty($value)) {
			return Purifier::purifyHtml($value);
		}

		return $value;
	}

	/**
	 * Get data map
	 */
	public function getAllRaw()
	{
		return $this->rawValueMap;
	}

	/**
	 * Get data map
	 */
	public function getAll()
	{
		foreach ($this->rawValueMap as $key => $value) {
			$this->get($key);
		}

		return $this->valueMap;
	}

	/**
	 * Check for existence of key
	 */
	public function has($key)
	{
		return isset($this->rawValueMap[$key]) || isset($this->valueMap[$key]);
	}

	/**
	 * Is the value (linked to key) empty?
	 */
	public function isEmpty($key)
	{
		if (isset($this->rawValueMap[$key])) {
			return empty($this->rawValueMap[$key]);
		}

		return true;
	}

	/**
	 * Get the raw value (if present) ignoring primary value.
	 */
	public function getRaw($key, $defvalue = '')
	{
		if (isset($this->rawValueMap[$key])) {
			return $this->rawValueMap[$key];
		}

		return $this->get($key, $defvalue);
	}

	/**
	 * Set the value for key
	 */
	public function set($key, $newvalue)
	{
		$this->valueMap[$key] = $newvalue;
	}

	/**
	 * Set the value for key
	 */
	public function delete($key)
	{
		unset($this->valueMap[$key]);
		unset($this->rawValueMap[$key]);
	}

	/**
	 * Set the value for key, both in the object as well as global $_REQUEST variable
	 */
	public function setGlobal($key, $newvalue)
	{
		$this->set($key, $newvalue);
	}

	/**
	 * Set default value for key
	 */
	public function setDefault($key, $defvalue)
	{
		$this->defaultmap[$key] = $defvalue;
	}

	/**
	 * Shorthand function to get value for (key=_operation|operation)
	 */
	public function getOperation()
	{
		return $this->get('_operation', $this->get('operation'));
	}

	/**
	 * Shorthand function to get value for (key=_session)
	 */
	public function getSession()
	{
		return $this->get('_session', $this->get('session'));
	}

	/**
	 * Shorthand function to get value for (key=mode)
	 */
	public function getMode()
	{
		return $this->get('mode');
	}

	public function getHeaders()
	{
		if (!empty($this->headers)) {
			return $this->headers;
		}

		if (!function_exists('apache_request_headers')) {
			foreach ($_SERVER as $key => $value) {
				if (substr($key, 0, 5) === 'HTTP_') {
					$key = str_replace(' ', '-', strtoupper(str_replace('_', ' ', substr($key, 5))));
					$out[$key] = $value;
				} else {
					$out[$key] = $value;
				}
			}

			$headers = $out;
		} else {
			$headers = array_change_key_case(apache_request_headers(), CASE_UPPER);
		}

		$this->headers = $headers;
		return $headers;
	}

	public function getHeader($key)
	{
		if (empty($this->headers)) {
			$this->getHeaders();
		}

		return isset($this->headers[$key]) ? $this->headers[$key] : null;
	}

	public function getRequestMethod()
	{
		$method = $_SERVER['REQUEST_METHOD'];
		if ($method == 'POST' && array_key_exists('HTTP_X_HTTP_METHOD', $_SERVER)) {
			if ($_SERVER['HTTP_X_HTTP_METHOD'] == 'DELETE') {
				$method = 'DELETE';
			} elseif ($_SERVER['HTTP_X_HTTP_METHOD'] == 'PUT') {
				$method = 'PUT';
			} else {
				throw new Exception('Unexpected Header');
			}
		}

		return $method;
	}

	public function getModule($raw = true)
	{
		$moduleName = $this->get('module');
		if (!$raw) {
			$parentModule = $this->get('parent');
			if (!empty($parentModule) && $parentModule == 'Settings') {
				$moduleName = $parentModule . ':' . $moduleName;
			}
		}

		return $moduleName;
	}

	public function isAjax()
	{
		if (!empty($_SERVER['HTTP_X_PJAX']) && $_SERVER['HTTP_X_PJAX'] === true) {
			return true;
		}

		return !empty($_SERVER['HTTP_X_REQUESTED_WITH']);
	}

	/**
	 * Validating incoming request.
	 */
	public function validateReadAccess()
	{
		$this->validateReferer();
		return true;
	}

	public function validateWriteAccess($skipRequestTypeCheck = false)
	{
		if (!$skipRequestTypeCheck && $_SERVER['REQUEST_METHOD'] != 'POST') {
			throw new \App\Exceptions\Csrf('Invalid request - validate Write Access');
		}

		$this->validateReadAccess();
		$this->validateCSRF();
		return true;
	}

	protected function validateReferer()
	{
		// Use request's user instead of global
		if (!$this->hasUser()) {
			return true;
		}
		
		// Referer check if present - to over come 
		//Check for user post authentication.
		if (isset($_SERVER['HTTP_REFERER']) && (stripos($_SERVER['HTTP_REFERER'], \App\AppConfig::main('site_URL')) !== 0 && $this->get('module') != 'Install')) {
			throw new \App\Exceptions\Csrf('Illegal request');
		}

		return true;
	}

	/**
	 * Set authenticated user for this request
	 * @param \App\Modules\Users\Models\Record $user
	 * @return self
	 */
	public function setUser(\App\Modules\Users\Models\Record $user): self
	{
		$this->authenticatedUser = $user;
		return $this;
	}

	/**
	 * Get authenticated user
	 * @return \App\Modules\Users\Models\Record
	 * @throws \RuntimeException if not authenticated
	 */
	public function getUser(): \App\Modules\Users\Models\Record
	{
		if ($this->authenticatedUser === null) {
			throw new \RuntimeException('User not authenticated for this request');
		}
		return $this->authenticatedUser;
	}

	/**
	 * Check if user is authenticated on request
	 * @return bool
	 */
	public function hasUser(): bool
	{
		return $this->authenticatedUser !== null;
	}

	/**
	 * Get user ID (convenience method)
	 * @return int
	 */
	public function getUserId(): int
	{
		return $this->getUser()->getId();
	}

	/**
	 * Check if current user is admin
	 * @return bool
	 */
	public function isUserAdmin(): bool
	{
		return $this->hasUser() && $this->getUser()->isAdminUser();
	}

	protected function validateCSRF()
	{
		if (!\CSRF::check(false)) {
			throw new \App\Exceptions\Csrf('Unsupported request');
		}
	}

	// ===== SERVICE CONTAINER METHODS =====

	/**
	 * Get service from container
	 * @param string $serviceName
	 * @return mixed
	 * @throws \Exception
	 */
	public function getService($serviceName)
	{
		if (!isset($this->services[$serviceName])) {
			$this->services[$serviceName] = $this->createService($serviceName);
		}
		return $this->services[$serviceName];
	}

	/**
	 * Create service instance
	 * @param string $serviceName
	 * @return mixed
	 * @throws \Exception
	 */
	protected function createService($serviceName)
	{
		switch ($serviceName) {
			case 'SettingsModule':
				return new \App\Modules\Settings\Base\Models\Module();
			default:
				throw new \Exception("Unknown service: {$serviceName}");
		}
	}

	/**
	 * Get module model (convenience method)
	 * @param string $moduleName
	 * @return \App\Modules\Base\Models\Module
	 */
	public function getModuleModel($moduleName)
	{
		$cacheKey = "module_model_{$moduleName}";
		return $this->getCachedOrCompute($cacheKey, function() use ($moduleName) {
			return \App\Modules\Base\Models\Module::getInstance($moduleName);
		});
	}

	// ===== REQUEST-SCOPED CACHE METHODS =====

	/**
	 * Get cached value
	 * @param string $key
	 * @param mixed $default
	 * @return mixed
	 */
	public function getCached($key, $default = null)
	{
		return $this->cache[$key] ?? $default;
	}

	/**
	 * Set cached value
	 * @param string $key
	 * @param mixed $value
	 * @return self
	 */
	public function setCached($key, $value)
	{
		$this->cache[$key] = $value;
		return $this;
	}

	/**
	 * Check if key exists in cache
	 * @param string $key
	 * @return bool
	 */
	public function hasCached($key)
	{
		return isset($this->cache[$key]);
	}

	/**
	 * Get cached value or compute and cache it
	 * @param string $key
	 * @param callable $callback
	 * @return mixed
	 */
	public function getCachedOrCompute($key, callable $callback)
	{
		if (!$this->hasCached($key)) {
			$this->setCached($key, $callback());
		}
		return $this->getCached($key);
	}
}
