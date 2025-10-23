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
 * Abstract Controller Class
 */

namespace App\Runtime;

use App\Http\Vtiger_Request;


abstract class Vtiger_Controller
{

	public function __construct()
	{
		self::setHeaders();
	}

	public function checkPermission(\App\Http\Vtiger_Request $vtigerRequest)
	{
	}

	public function loginRequired()
	{
		return true;
	}

	abstract public function getViewer(\App\Http\Vtiger_Request $vtigerRequest);

	abstract public function process(\App\Http\Vtiger_Request $vtigerRequest);

	public function validateRequest(\App\Http\Vtiger_Request $vtigerRequest)
	{
		
	}

	public function preProcessAjax(\App\Http\Vtiger_Request $vtigerRequest)
	{
		
	}

	public function preProcess(\App\Http\Vtiger_Request $vtigerRequest)
	{
		
	}

	public function postProcess(\App\Http\Vtiger_Request $vtigerRequest)
	{
		
	}

	// Control the exposure of methods to be invoked from client (kind-of RPC)
	protected $exposedMethods = [];

	/**
	 * Function that will expose methods for external access
	 * @param string $name - method name
	 */
	protected function exposeMethod($name)
	{
		if (!in_array($name, $this->exposedMethods)) {
			$this->exposedMethods[] = $name;
		}
	}

	/**
	 * Function checks if the method is exposed for client usage
	 * @param string $name - method name
	 * @return boolean
	 */
	public function isMethodExposed($name)
    {
        return in_array($name, $this->exposedMethods);
    }

	/**
	 * Function invokes exposed methods for this class
	 * @param string $name - method name
	 * @param \App\Http\Vtiger_Request $request
	 * @throws Exception
	 */
	public function invokeExposedMethod(...$parameters)
	{
		$name = array_shift($parameters);
		if (!empty($name) && $this->isMethodExposed($name)) {
			return call_user_func_array([$this, $name], $parameters);
		}

		throw new \Exception(\App\Runtime\Vtiger_Language_Handler::translate('LBL_NOT_ACCESSIBLE'));
	}

	/**
	 * Set HTTP Headers
	 */
	public function setHeaders()
	{
		if (headers_sent()) {
			return;
		}

		$browser = \App\RequestUtil::getBrowserInfo();
		header('Expires: ' . gmdate('D, d M Y H:i:s') . ' GMT');
		header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
		if ($browser && is_object($browser) && $browser->ie && $browser->https) {
			header('Pragma: private');
			header('Cache-Control: private, must-revalidate');
		} else {
			header('Cache-Control: private, no-cache, no-store, must-revalidate, post-check=0, pre-check=0');
			header('Pragma: no-cache');
		}

		header('X-Frame-Options: SAMEORIGIN');
		header_remove('X-Powered-By');
	}
}
