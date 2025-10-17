<?php
/* {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} */


namespace FreeCRM\Modules\PaymentsOut\Actions;

class GenerateRecords extends \FreeCRM\Runtime\Vtiger_Action_Controller
{

	public function checkPermission(\FreeCRM\Http\Vtiger_Request $request)
	{
		$currentUserPriviligesModel = \FreeCRM\Modules\Users\Models\Privileges::getCurrentUserPrivilegesModel();
		if (!$currentUserPriviligesModel->hasModuleActionPermission($moduleName, 'Save')) {
			throw new \Exception\AppException(\FreeCRM\Runtime\Vtiger_Language_Handler::translate($moduleName) . ' ' . \FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_NOT_ACCESSIBLE'));
		}
	}

	public function process(\FreeCRM\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$bag = false;
		$paymentsOut = $request->get('paymentsOut');
		foreach ($paymentsOut as $fields) {
			$ossPaymentsOut = \FreeCRM\CRMEntity::getInstance($moduleName);
			$ossPaymentsOut->column_fields['paymentsname'] = 'Name';
			$ossPaymentsOut->column_fields['paymentsvalue'] = $fields['amount'];
			$ossPaymentsOut->column_fields['paymentscurrency'] = $fields['third_letter_currency_code'];
			$ossPaymentsOut->column_fields['paymentstitle'] = $fields['details']['title'];
			$ossPaymentsOut->column_fields['bank_account'] = $fields['details']['contAccount'];
			$saved = $ossPaymentsOut->save('PaymentsOut');
			if ($saved === false) {
				$bag = true;
			}
		}

		if ($bag)
			$result = ['success' => true, 'return' => \FreeCRM\Runtime\Vtiger_Language_Handler::translate('MSG_SAVE_OK', $moduleName)];
		else
			$result = ['success' => false, 'return' => \FreeCRM\Runtime\Vtiger_Language_Handler::translate('MSG_SAVE_ERROR', $moduleName)];

		$response = new \FreeCRM\Http\Vtiger_Response();
		$response->setResult($result);
		$response->emit();
	}
}
