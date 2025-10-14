<?php

namespace FreeCRM\Modules\Settings\Currency\Actions;


/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * ********************************************************************************** */

use FreeCRM\Modules\Settings\Currency\Models\Module as Settings_Currency_Module_Model;
class DeleteAjax extends \FreeCRM\Modules\Settings\Vtiger\Actions\Basic
{

	public function process(\FreeCRM\Http\Vtiger_Request $request)
	{
		$response = new \FreeCRM\Http\Vtiger_Response();
		try {
			$record = $request->get('record');
			$transforCurrencyToId = $request->get('transform_to_id');
			if (empty($transforCurrencyToId)) {
				throw new \Exception('Transfer currency id cannot be empty');
			}
			Settings_Currency_Module_Model::delete($record);
			$response->setResult(array('success' => 'true'));
		} catch (Exception $e) {
			$response->setError($e->getCode(), $e->getMessage());
		}
		$response->emit();
	}

	public function validateRequest(\FreeCRM\Http\Vtiger_Request $request)
	{
		$request->validateWriteAccess();
	}
}
