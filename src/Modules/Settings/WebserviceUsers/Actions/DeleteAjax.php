<?php

namespace App\Modules\Settings\WebserviceUsers\Actions;



/**
 * Class to delete
 * @package YetiForce.Settings.Action
 * @license licenses/License.html
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 */

class DeleteAjax extends \App\Modules\Settings\Vtiger\Actions\Delete
{

	/**
	 * Function  proccess
	 * @param \App\Http\Vtiger_Request $request
	 */
	public function process(\App\Http\Vtiger_Request $request)
	{
		$recordId = $request->get('record');
		$typeApi = $request->get('typeApi');
		$recordModel = \App\Modules\Settings\WebserviceUsers\Models\Record::getInstanceById($recordId, $typeApi);
		$result = $recordModel->delete();

		$responceToEmit = new \App\Http\Vtiger_Response();
		$responceToEmit->setResult($result);
		$responceToEmit->emit();
	}

	/**
	 * Validating incoming request.
	 * @param \App\Http\Vtiger_Request $request
	 */
	public function validateRequest(\App\Http\Vtiger_Request $request)
	{
		$request->validateReadAccess();
	}
}
