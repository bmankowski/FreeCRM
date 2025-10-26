<?php
/* {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} */


namespace App\Modules\PaymentsOut\Actions;

class GenerateRecords extends \App\Runtime\BaseActionController
{

	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		$currentUserPriviligesModel = \App\Modules\Users\Models\Privileges::getCurrentUserPrivilegesModel();
		if (!$currentUserPriviligesModel->hasModuleActionPermission($moduleName, 'Save')) {
			throw new \Exception\AppException(\App\Runtime\Vtiger_Language_Handler::translate($moduleName) . ' ' . \App\Runtime\Vtiger_Language_Handler::translate('LBL_NOT_ACCESSIBLE'));
		}
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$bag = false;
		$paymentsOut = $request->get('paymentsOut');
		foreach ($paymentsOut as $fields) {
			$ossPaymentsOut = \App\CRMEntity::getInstance($moduleName);
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
			$result = ['success' => true, 'return' => \App\Runtime\Vtiger_Language_Handler::translate('MSG_SAVE_OK', $moduleName)];
		else
			$result = ['success' => false, 'return' => \App\Runtime\Vtiger_Language_Handler::translate('MSG_SAVE_ERROR', $moduleName)];

		$response = new \App\Http\Vtiger_Response();
		$response->setResult($result);
		$response->emit();
	}
}
