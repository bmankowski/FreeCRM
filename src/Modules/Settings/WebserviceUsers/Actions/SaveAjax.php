<?php

namespace App\Modules\Settings\WebserviceUsers\Actions;



/**
 * Save Application
 * @package YetiForce.Settings.Action
 * @license licenses/License.html
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 */

class SaveAjax extends \App\Modules\Settings\Vtiger\Actions\Save
{

	/**
	 * Save
	 * @param \App\Http\Vtiger_Request $request
	 */
	public function process(\App\Http\Vtiger_Request $request)
	{
		$data = $request->get('param');
		$typeApi = $request->get('typeApi');
		$recordId = $request->get('record');
		$qualifiedModuleName = $request->getModule(false);
		if ($recordId) {
			$recordModel = \App\Modules\Settings\WebserviceUsers\Models\Record::getInstanceById($recordId, $typeApi);
		} else {
			$recordModel = \App\Modules\Settings\WebserviceUsers\Models\Record::getCleanInstance($typeApi);
		}
		$result = $recordModel->save($data);

		$responceToEmit = new \App\Http\Vtiger_Response();
		$responceToEmit->setResult($result);
		$responceToEmit->emit();
	}
}
