<?php

namespace FreeCRM\Modules\Settings\Vtiger\Views;


/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * ********************************************************************************** */

class ListAjax extends \FreeCRM\Modules\Settings\Vtiger\Views\List
{

	public function __construct()
	{
		parent::__construct();
	}

	public function preProcess(\FreeCRM\Http\Vtiger_Request $request, $display = true)
	{
		return true;
	}

	public function postProcess(\FreeCRM\Http\Vtiger_Request $request)
	{
		return true;
	}

	public function process(\FreeCRM\Http\Vtiger_Request $request)
	{
		$mode = $request->get('mode');
		if (!empty($mode)) {
			$this->invokeExposedMethod($mode, $request);
			return;
		}
	}
}
