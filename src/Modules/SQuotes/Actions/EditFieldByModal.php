<?php

namespace App\Modules\SQuotes\Actions;

/**
 * EditFieldByModal Class
 * @package YetiForce.Action
 * @license licenses/License.html
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 */
class EditFieldByModal extends \App\Runtime\BaseActionController
{

	public function process(\App\Http\Vtiger_Request $request)
	{
		$params = $request->get('param');
		$moduleName = $request->getModule();
		$recordId = $params['record'];
		$state = $params['state'];
		$fieldName = $params['fieldName'];

		$recordModel = \App\Modules\Base\Models\Record::getInstanceById($recordId, $moduleName);
		$recordModel->set('id', $recordId);
		$recordModel->set($fieldName, $state);
		if (in_array($state, ['PLL_CANCELLED', 'PLL_ACCEPTED'])) {
			$currentTime = date('Y-m-d H:i:s');
			$responseTime = strtotime($currentTime) - strtotime($recordModel->get('createdtime'));
			$recordModel->set('response_time', $responseTime / 60 / 60);
			$recordModel->set('closedtime', $currentTime);
		}
		$recordModel->save();

		$response = new \App\Http\Vtiger_Response();
		$response->setResult(['success' => true]);
		$response->emit();
	}
}
