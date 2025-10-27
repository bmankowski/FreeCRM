<?php
/* {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} */


namespace App\Modules\PaymentsIn\Actions;

class GenerateRecords extends \App\Base\Controllers\BaseActionController
{

	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		$currentUserPriviligesModel = \App\Modules\Users\Models\Privileges::getCurrentUserPrivilegesModel();
		if (!$currentUserPriviligesModel->hasModuleActionPermission($moduleName, 'Save')) {
			throw new \App\Exceptions\AppException(\App\Runtime\Vtiger_Language_Handler::translate($moduleName) . ' ' . \App\Runtime\Vtiger_Language_Handler::translate('LBL_NOT_ACCESSIBLE'));
		}
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$bag = false;
		$paymentsIn = $request->get('paymentsIn');
		foreach ($paymentsIn as $fields) {
			$ossPaymentsIn = \App\CRMEntity::getInstance($moduleName);
			$ossPaymentsIn->column_fields['paymentsname'] = 'Name';
			$ossPaymentsIn->column_fields['paymentsvalue'] = $fields['amount'];
			$ossPaymentsIn->column_fields['paymentscurrency'] = $fields['third_letter_currency_code'];
			$ossPaymentsIn->column_fields['paymentstitle'] = $fields['details']['title'];
			$ossPaymentsIn->column_fields['bank_account'] = $fields['details']['contAccount'];
			$saved = $ossPaymentsIn->save('PaymentsIn');
			if ($saved === false) {
				$bag = true;
			}
		}

		if ($bag) {
			$result = ['success' => true, 'return' => \App\Runtime\Vtiger_Language_Handler::translate('MSG_SAVE_OK', $moduleName)];
		} else {
			$result = ['success' => false, 'return' => \App\Runtime\Vtiger_Language_Handler::translate('MSG_SAVE_ERROR', $moduleName)];
		}

		$response = new \App\Http\Vtiger_Response();
		$response->setResult($result);
		$response->emit();
	}
}
