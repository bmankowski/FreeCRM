<?php

namespace FreeCRM\Modules\Settings\CurrencyUpdate\Actions;
use FreeCRM\Modules\Settings\CurrencyUpdate\Models\Module as Settings_CurrencyUpdate_Module_Model;



/**
 * @package YetiForce.actions
 * @license licenses/License.html
 * @author Maciej Stencel <m.stencel@yetiforce.com>
 */
class SaveActiveBank extends \FreeCRM\Runtime\Vtiger_Action_Controller
{

	public function checkPermission(\FreeCRM\Http\Vtiger_Request $request)
	{
		return true;
	}

	public function process(\FreeCRM\Http\Vtiger_Request $request)
	{
		$id = $request->get('id');
		$moduleModel = Settings_CurrencyUpdate_Module_Model::getCleanInstance();

		if (!$moduleModel->setActiveBankById($id)) {
			$return = array('success' => false, 'message' => \FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_SET_BANK_ERROR', 'Settings:CurrencyUpdate'));
		} else {
			$return = array('success' => true, 'message' => \FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_SET_BANK_OK', 'Settings:CurrencyUpdate'));
		}
		$response = new \FreeCRM\Http\Vtiger_Response();
		$response->setResult($return);
		$response->emit();
	}
}
