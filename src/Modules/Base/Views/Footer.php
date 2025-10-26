<?php
/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

namespace App\Modules\Base\Views;
abstract class Footer extends Header
{

	public function __construct()
	{
		parent::__construct();
	}
	//Note: To get the right hook for immediate parent in PHP,
	// specially in case of deep hierarchy
	/* function preProcessParentTplName(\App\Http\Vtiger_Request $request) {
	  return parent::preProcessTplName($request);
	  } */

	/* function postProcess(\App\Http\Vtiger_Request $request) {
	  parent::postProcess($request);
	  } */
}
