<?php

namespace App\Modules\Settings\CurrencyUpdate\Actions;
use App\Modules\Settings\CurrencyUpdate\Models\Module as Settings_CurrencyUpdate_Module_Model;



/**
 * @package YetiForce.actions
 * @license licenses/License.html
 * @author Maciej Stencel <m.stencel@yetiforce.com>
 */
class SaveActiveBank extends \App\Runtime\Vtiger_Action_Controller
{

	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		return true;
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$id = $request->get('id');
		$moduleModel = Settings_CurrencyUpdate_Module_Model::getCleanInstance();

		if (!$moduleModel->setActiveBankById($id)) {
			$return = array('success' => false, 'message' => \App\Runtime\Vtiger_Language_Handler::translate('LBL_SET_BANK_ERROR', 'Settings:CurrencyUpdate'));
		} else {
			$return = array('success' => true, 'message' => \App\Runtime\Vtiger_Language_Handler::translate('LBL_SET_BANK_OK', 'Settings:CurrencyUpdate'));
		}
		$response = new \App\Http\Vtiger_Response();
		$response->setResult($return);
		$response->emit();
	}
}
