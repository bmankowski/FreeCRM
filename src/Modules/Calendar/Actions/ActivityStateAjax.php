<?php

namespace FreeCRM\Modules\Calendar\Actions;

/**
 *
 * @package YetiForce.actions
 * @license licenses/License.html
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */
class ActivityStateAjax extends \FreeCRM\Runtime\Vtiger_Action_Controller
{

	public function process(\FreeCRM\Http\Vtiger_Request $request)
	{
		$recordId = $request->get('record');
		$state = $request->get('state');

		$recordModel = \FreeCRM\Modules\Vtiger\Models\Record::getInstanceById($recordId);
		$recordModel->set('activitystatus', $state);
		$recordModel->save();

		$response = new \FreeCRM\Http\Vtiger_Response();
		$response->setResult(['success' => true]);
		$response->emit();
	}
}
