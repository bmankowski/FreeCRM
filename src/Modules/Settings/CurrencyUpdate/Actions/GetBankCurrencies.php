<?php

namespace FreeCRM\Modules\Settings\CurrencyUpdate\Actions;
use FreeCRM\Modules\Settings\CurrencyUpdate\Models\Module as Settings_CurrencyUpdate_Module_Model;



/**
 * @package YetiForce.actions
 * @license licenses/License.html
 * @author Maciej Stencel <m.stencel@yetiforce.com>
 */
class GetBankCurrencies extends \FreeCRM\Runtime\Vtiger_Action_Controller
{

	public function checkPermission(\FreeCRM\Http\Vtiger_Request $request)
	{
		return true;
	}

	public function process(\FreeCRM\Http\Vtiger_Request $request)
	{
		$mode = $request->get('mode');
		$name = 'Settings_CurrencyUpdate_models_' . $request->get('name') . '_BankModel';
		$moduleModel = Settings_CurrencyUpdate_Module_Model::getCleanInstance();
		$response = new \FreeCRM\Http\Vtiger_Response();

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
