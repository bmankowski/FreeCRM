<?php

namespace App\Modules\Products\Actions;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

class SaveAjax extends \App\Modules\Vtiger\Actions\Save
{

	public function process(\App\Http\Vtiger_Request $request)
	{
		//the new values are added to $_REQUEST for Ajax Save, are removing the Tax details depend on the 'ajxaction' value
		\App\Http\AppRequest::set('ajxaction', 'DETAILVIEW');
		parent::process($request);
	}
}
