<?php
/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */


use FreeCRM\Http\Vtiger_Request;
class SMSNotifier_Edit_View extends Vtiger_Edit_View
{

	public function checkPermission(\FreeCRM\Http\Vtiger_Request $request)
	{
		throw new \Exception\NoPermitted('LBL_PERMISSION_DENIED');
	}
}
