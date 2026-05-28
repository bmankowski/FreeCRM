<?php

namespace App\Modules\Settings\Base\Actions;
use App\Modules\Settings\Base\Models\TermsAndConditions;


/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

class TermsAndConditionsSaveAjax extends \App\Modules\Settings\Base\Actions\Basic
{

	public function process(\App\Http\Vtiger_Request $request)
	{
		$model = \App\Modules\Settings\Base\Models\TermsAndConditions::getInstance();
		$model->setText($request->get('tandc'));
		$model->save();

		$response = new \App\Http\Vtiger_Response();
		$response->emit();
	}

	public function validateRequest(\App\Http\Vtiger_Request $request)
	{
		$request->validateWriteAccess();
	}
}
