<?php

namespace App\Modules\Contacts\Views;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */


use App\Http\Vtiger_Request;
class QuickCreateAjax extends \App\Modules\Base\Views\QuickCreateAjax
{

	public function process(\App\Http\Vtiger_Request $request)
	{
		$viewer = $this->getViewer($request);

		$moduleName = $request->getModule();
		$moduleModel = \App\Modules\Base\Models\Module::getInstance($moduleName);
		$salutationFieldModel = \App\Modules\Base\Models\Field::getInstance('salutationtype', $moduleModel);
		$viewer->assign('SALUTATION_FIELD_MODEL', $salutationFieldModel);
		parent::process($request);
	}
}
