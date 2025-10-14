<?php

namespace FreeCRM\Modules\Settings\Vtiger\Actions;
use FreeCRM\Modules\Settings\Vtiger\Models\TermsAndConditions;


/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

class TermsAndConditionsSaveAjax extends \FreeCRM\Modules\Settings\Vtiger\Actions\Basic
{

	public function process(\FreeCRM\Http\Vtiger_Request $request)
	{
		$model = \FreeCRM\Modules\Settings\Vtiger\Models\TermsAndConditions::getInstance();
		$model->setText($request->get('tandc'));
		$model->save();

		$response = new \FreeCRM\Http\Vtiger_Response();
		$response->emit();
	}

	public function validateRequest(\FreeCRM\Http\Vtiger_Request $request)
	{
		$request->validateWriteAccess();
	}
}
