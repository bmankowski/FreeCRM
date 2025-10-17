<?php

namespace App\Modules\Settings\Workflows\Views;


/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * ********************************************************************************** */

class List extends \App\Modules\Settings\Vtiger\Views\List
{

	public function preProcess(\App\Http\Vtiger_Request $request, $display = true)
	{
		$viewer = $this->getViewer($request);
		$viewer->assign('SUPPORTED_MODULE_MODELS', \App\Modules\Settings\Workflows\Models\Module::getSupportedModules());
		$viewer->assign('CRON_RECORD_MODEL', \App\Modules\Settings\CronTasks\Models\Record::getInstanceByName('LBL_WORKFLOW'));
		parent::preProcess($request, $display);
	}
}
