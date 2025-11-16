<?php
/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * ********************************************************************************** */

namespace App\Http;

// Import dependencies
include_once 'libraries/HTTP_Session/Session.php';

/**
 * Session class
 */

class Vtiger_Session
{

	/**
	 * Constructor
	 * Avoid creation of instances.
	 */
	private function __construct()
	{
		
	}

	/**
	 * Destroy session
	 */
	public static function destroy($sessionid = false)
	{
		\HTTP_Session::destroy($sessionid);
	}

	/**
	 * Calls session_regenerate_id() if available
	 */
	public static function regenerateId($deleteOldSessionData = false)
	{
		\HTTP_Session::regenerateId($deleteOldSessionData);
	}

	/**
	 * Initialize session
	 */
	public static function init($sessionid = false)
	{
		if (empty($sessionid)) {
			\HTTP_Session::start(null, null);
			$sessionid = \HTTP_Session::id();
		} else {
			\HTTP_Session::start(null, $sessionid);
		}

		if (\HTTP_Session::isIdle() || \HTTP_Session::isExpired()) {
			return false;
		}
		return $sessionid;
	}

	/**
	 * Is key defined in session?
	 */
	public static function has($key)
	{
		return \HTTP_Session::is_set($key);
	}

	/**
	 * Get value for the key.
	 */
	public static function get($key, $defvalue = '')
	{
		return \HTTP_Session::get($key, $defvalue);
	}

	/**
	 * Set value for the key.
	 */
	public static function set($key, $value)
	{
		\HTTP_Session::set($key, $value);
	}

	/**
	 * Set value for the key.
	 */
	public static function remove($name)
	{
		unset($_SESSION[$name]);
	}

	/**
	 * Set authenticated user ID in session
	 * @param int $userId
	 */
	public static function setAuthenticatedUserId(int $userId): void
	{
		self::set('authenticated_user_id', $userId);
		self::set('app_unique_key', \App\Core\AppConfig::main('application_unique_key'));
	}

	/**
	 * Get authenticated user ID from session
	 * @return int|null
	 */
	public static function getAuthenticatedUserId(): ?int
	{
		if (!self::has('authenticated_user_id')) {
			return null;
		}
		
		$userId = self::get('authenticated_user_id');
		$appKey = self::get('app_unique_key');
		
		if (\App\Core\AppConfig::main('application_unique_key') !== $appKey) {
			return null;
		}
		
		return (int) $userId;
	}

	/**
	 * Check if user is authenticated
	 * @return bool
	 */
	public static function isAuthenticated(): bool
	{
		return self::getAuthenticatedUserId() !== null;
	}

	/**
	 * Clear authentication data
	 */
	public static function clearAuthentication(): void
	{
		self::remove('authenticated_user_id');
		self::remove('app_unique_key');
		self::remove('baseUserId');
	}
}
