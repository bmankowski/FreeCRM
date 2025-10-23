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

/**
 * Abstract Action Controller Class
 */
abstract class Vtiger_Action_Controller extends \App\Runtime\Vtiger_Controller
{

	public function __construct()
	{
		parent::__construct();
	}

	public function getViewer(\App\Http\Vtiger_Request $vtigerRequest)
	{
		throw new \Exception('Action - implement getViewer - JSONViewer');
	}

	public function validateRequest(\App\Http\Vtiger_Request $vtigerRequest)
	{
		return $vtigerRequest->validateReadAccess();
	}

	public function preProcess(\App\Http\Vtiger_Request $vtigerRequest)
	{
		return true;
	}

	protected function preProcessDisplay(\App\Http\Vtiger_Request $vtigerRequest)
	{
		
	}

	protected function preProcessTplName(\App\Http\Vtiger_Request $vtigerRequest)
	{
		return false;
	}

	public function postProcess(\App\Http\Vtiger_Request $vtigerRequest)
	{
		return true;
	}
}

