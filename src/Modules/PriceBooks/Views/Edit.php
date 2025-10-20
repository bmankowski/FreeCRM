<?php

namespace App\Modules\PriceBooks\Views;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */


use App\Http\Vtiger_Request;
class Edit  extends \App\Modules\Vtiger\Views\Index
{

	public function process(\App\Http\Vtiger_Request $request)
	{
		$viewer = $this->getViewer($request);
		$viewer->assign('IS_RELATION', $request->get('relationOperation'));
		parent::process($request);
	}
}
