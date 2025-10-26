<?php

namespace App\Modules\Calendar\Actions;

/**
 *
 * @package YetiForce.actions
 * @license licenses/License.html
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */
class ActivityStateAjax extends \App\Runtime\BaseActionController
{

	public function process(\App\Http\Vtiger_Request $request)
	{
		$recordId = $request->get('record');
		$state = $request->get('state');

		$recordModel = \App\Modules\Base\Models\Record::getInstanceById($recordId);
		$recordModel->set('activitystatus', $state);
		$recordModel->save();

		$response = new \App\Http\Vtiger_Response();
		$response->setResult(['success' => true]);
		$response->emit();
	}
}
