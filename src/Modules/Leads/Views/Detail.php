<?php

namespace App\Modules\Leads\Views;

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
		
		// Assign Leads-specific data
		$viewer = $this->getViewer($request);
		$viewer->assign('CONVERSION_AVAILABLE_STATUS', \App\Json::encode(\App\Modules\Leads\Models\Module::getConversionAvaibleStatuses()));
	}

}
