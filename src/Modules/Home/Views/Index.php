<?php

namespace FreeCRM\Modules\Home\Views;

/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * ********************************************************************************** */


use FreeCRM\Modules\Vtiger\Views\Index as VtigerIndex;
use FreeCRM\Http\Vtiger_Request;
class Index extends VtigerIndex
{

	public function process(\FreeCRM\Http\Vtiger_Request $request)
	{
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();

		$viewer->view('Index.tpl', $moduleName);
	}
}
