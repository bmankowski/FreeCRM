<?php

namespace App\Modules\Settings\AutomaticAssignment\Actions;



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
		$qualifiedModuleName = $request->getModule(false);
		$recordId = $request->get('record');
		$recordModel = \App\Modules\Settings\AutomaticAssignment\Models\Record::getInstanceById($recordId);
		$recordModel->delete();

		$responceToEmit = new \App\Http\Vtiger_Response();
		$responceToEmit->setResult($recordModel->getId());
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
