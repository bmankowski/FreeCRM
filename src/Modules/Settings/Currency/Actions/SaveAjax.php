<?php

namespace App\Modules\Settings\Currency\Actions;


/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * ********************************************************************************** */

class SaveAjax extends \App\Modules\Settings\Base\Actions\Basic
{

	public function process(\App\Http\Vtiger_Request $request)
	{

		$record = $request->get('record');
		if (empty($record)) {
			//get instance from currency name, Aleady deleted and adding again same currency case 
			$recordModel = \App\Modules\Settings\Currency\Models\Record::getInstance($request->get('currency_name'));
			if (empty($recordModel)) {
				$recordModel = new \App\Modules\Settings\Currency\Models\Record();
			}
		} else {
			$recordModel = \App\Modules\Settings\Currency\Models\Record::getInstance($record);
		}

		$fieldList = array('currency_name', 'conversion_rate', 'currency_status', 'currency_code', 'currency_symbol');

		foreach ($fieldList as $fieldName) {
			if ($request->has($fieldName)) {
				$recordModel->set($fieldName, $request->get($fieldName));
			}
		}
		//To make sure we are saving record as non deleted. This is useful if we are adding deleted currency
		$recordModel->set('deleted', 0);
		$response = new \App\Http\Vtiger_Response();
		try {
			if ($request->get('currency_status') == 'Inactive' && !empty($record)) {
				$transforCurrencyToId = $request->get('transform_to_id');
				if (empty($transforCurrencyToId)) {
					throw new \Exception('Transfer currency id cannot be empty');
				}
			}
			$id = $recordModel->save();
			$recordModel = \App\Modules\Settings\Currency\Models\Record::getInstance($id);
			$response->setResult(array_merge($recordModel->getData(), array('record' => $recordModel->getId())));
		} catch (Exception $e) {
			$response->setError($e->getCode(), $e->getMessage());
		}
		$response->emit();
	}

	public function validateRequest(\App\Http\Vtiger_Request $request)
	{
		$request->validateWriteAccess();
	}
}
