<?php

namespace App\Modules\Settings\CurrencyUpdate\Actions;



/**
 * @package YetiForce.actions
 * @license licenses/License.html
 * @author Maciej Stencel <m.stencel@yetiforce.com>
 */
class GetBankCurrencies extends \App\Runtime\Vtiger_Action_Controller
{

	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		return true;
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$mode = $request->get('mode');
		$name = 'Settings_CurrencyUpdate_models_' . $request->get('name') . '_BankModel';
		$moduleModel = Settings_CurrencyUpdate_Module_Model::getCleanInstance();
		$response = new \App\Http\Vtiger_Response();

		if ($mode == 'supported') {
			$supported = $moduleModel->getSupportedCurrencies($name);
			$response->setResult($supported);
		} else {
			$unsupported = $moduleModel->getUnSupportedCurrencies($name);
			$response->setResult($unsupported);
		}

		$response->emit();
	}
}
