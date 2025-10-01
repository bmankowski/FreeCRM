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

		// Disable JavaScript rewriting for all requests (not just AJAX)
		// This prevents issues with public directory structure
		CSRF::$frameBreaker = false;
		CSRF::$rewriteJs = null;
		CSRF::$rewrite = false;
		
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
