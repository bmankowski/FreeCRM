<?php
/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * ********************************************************************************** */

namespace App\EntryPoint;

use App\Http\Vtiger_Request;

/**
 * Abstract Entry Point
 * 
 * Base class for all application entry points (WebUI, CLI, API, etc.)
 */
abstract class EntryPoint
{
	/**
	 * Login data
	 */
	protected $login = false;

	/**
	 * Get login data.
	 */
	public function getLogin()
	{
		return $this->login;
	}

	/**
	 * Set login data.
	 */
	public function setLogin($login)
	{
		if ($this->login) {
            throw new \App\Exceptions\AppException('Login is already set.');
        }

        $this->login = $login;
	}

	/**
	 * Check if login data is present.
	 */
	public function hasLogin()
	{
		return (bool) $this->getLogin();
	}

	abstract public function process(\App\Http\Vtiger_Request $request);
}
