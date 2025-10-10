<?php
/* {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} */


namespace FreeCRM\Modules\PaymentsIn\Actions;

class GenerateRecords extends \FreeCRM\Runtime\Vtiger_Action_Controller
{

	public function checkPermission(\FreeCRM\Http\Vtiger_Request $request)
	{
		$currentUserPriviligesModel = \FreeCRM\Modules\Users\Models\Privileges::getCurrentUserPrivilegesModel();
		if (!$currentUserPriviligesModel->hasModuleActionPermission($moduleName, 'Save')) {
			throw new \Exception\AppException(vtranslate($moduleName) . ' ' . vtranslate('LBL_NOT_ACCESSIBLE'));
		}
	}

	public function process(\FreeCRM\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$bag = false;
		$paymentsIn = $request->get('paymentsIn');
		foreach ($paymentsIn as $fields) {
			$ossPaymentsIn = \FreeCRM\CRMEntity::getInstance($moduleName);
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
			$result = ['success' => true, 'return' => vtranslate('MSG_SAVE_OK', $moduleName)];
		} else {
			$result = ['success' => false, 'return' => vtranslate('MSG_SAVE_ERROR', $moduleName)];
		}

		$response = new \FreeCRM\Http\Vtiger_Response();
		$response->setResult($result);
		$response->emit();
	}
}
