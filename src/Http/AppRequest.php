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

class AppRequest
{

	private static $request = false;

	public static function init()
	{
		if (!self::$request) {
			self::$request = new Vtiger_Request($_REQUEST, $_REQUEST);
		}

		return self::$request;
	}

	public static function get($key, $defvalue = '')
	{
		if (!self::$request) {
			static::init();
		}

		return self::$request->get($key, $defvalue);
	}

	public static function has($key)
	{
		if (!self::$request) {
			static::init();
		}

		return self::$request->has($key);
	}

	public static function getForSql($key, $skipEmtpy = true)
	{
		if (!self::$request) {
			static::init();
		}

		return self::$request->getForSql($key, $skipEmtpy);
	}

	public static function set($key, $value)
	{
		if (!self::$request) {
			static::init();
		}

		return self::$request->set($key, $value);
	}

	public static function isEmpty($key)
	{
		if (!self::$request) {
			static::init();
		}

		return self::$request->isEmpty($key);
	}

	public static function isAjax()
	{
		if (!empty($_SERVER['HTTP_X_PJAX']) && $_SERVER['HTTP_X_PJAX'] === true) {
            return true;
        }

        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']);
	}
}

