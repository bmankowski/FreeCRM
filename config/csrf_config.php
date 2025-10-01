<?php
/* +*******************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * ****************************************************************************** */

class CSRFConfig
{

	/**
	 * Specific custom config startup for CSRF 
	 */
	public static function startup()
	{
		//Override the default expire time of token 
		CSRF::$expires = 259200;

		// Enable JavaScript support for AJAX requests
		CSRF::$frameBreaker = false;
		CSRF::$rewriteJs = 'libraries/csrf-magic/csrf-magic.js';
		
		// Force output buffering to be enabled
		if (ob_get_level() > 0) {
			// If there's already output buffering, we need to start our own
			ob_start([CSRF::class, 'obHandler']);
		}
		CSRF::$rewrite = true;
		
		// Enable defer mode - let the application handle CSRF checking manually
		CSRF::$defer = true;
	}

	public static function isAjax()
	{
		if (!empty($_SERVER['HTTP_X_PJAX']) && $_SERVER['HTTP_X_PJAX'] === true) {
			return true;
		} elseif (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
			return true;
		}
		return false;
	}
}
