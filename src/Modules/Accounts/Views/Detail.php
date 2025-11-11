<?php

namespace App\Modules\Accounts\Views;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */


class Detail extends \App\Modules\Base\Views\Detail
{

	public function preProcess(\App\Http\Vtiger_Request $request, $display = true)
	{
		parent::preProcess($request, false);
		
		// Prepare Accounts-specific data in controller instead of calling functions in template
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		$viewer->assign('COUNT_IN_HIERARCHY', \App\AppConfig::module($moduleName, 'COUNT_IN_HIERARCHY'));
	}

}

