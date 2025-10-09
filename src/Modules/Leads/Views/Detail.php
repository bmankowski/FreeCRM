<?php

namespace FreeCRM\Modules\Leads\Views;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */


use FreeCRM\Http\Vtiger_Request;
class Detail extends View
{

	public function preProcess(\FreeCRM\Http\Vtiger_Request $request, $display = true)
	{
		$viewer = $this->getViewer($request);
		$viewer->assign('CONVERSION_AVAILABLE_STATUS', \App\Json::encode(Leads_Module_Model::getConversionAvaibleStatuses()));
		parent::preProcess($request);
	}
}
