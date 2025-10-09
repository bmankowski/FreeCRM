<?php

namespace FreeCRM\Modules\Events\Views;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */


use FreeCRM\Http\Vtiger_Request;
class Calendar extends View
{

	public function preProcess(\FreeCRM\Http\Vtiger_Request $request, $display = true)
	{
		
	}

	public function postProcess(\FreeCRM\Http\Vtiger_Request $request)
	{
		
	}

	public function process(\FreeCRM\Http\Vtiger_Request $request)
	{
		header("Location: index.php?module=Calendar&view=Calendar");
	}
}
