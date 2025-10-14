<?php

namespace FreeCRM\Modules\Settings\AutomaticAssignment\Actions;



/**
 * Class to delete
 * @package YetiForce.Settings.Action
 * @license licenses/License.html
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 */

use FreeCRM\Modules\Settings\AutomaticAssignment\Models\Record as Settings_AutomaticAssignment_Record_Model;
class DeleteAjax extends \FreeCRM\Modules\Settings\Vtiger\Actions\Delete
{

	/**
	 * Function  proccess
	 * @param \FreeCRM\Http\Vtiger_Request $request
	 */
	public function process(\FreeCRM\Http\Vtiger_Request $request)
	{
		$qualifiedModuleName = $request->getModule(false);
		$recordId = $request->get('record');
		$recordModel = Settings_AutomaticAssignment_Record_Model::getInstanceById($recordId);
		$recordModel->delete();

		$responceToEmit = new \FreeCRM\Http\Vtiger_Response();
		$responceToEmit->setResult($recordModel->getId());
		$responceToEmit->emit();
	}

	/**
	 * Validating incoming request.
	 * @param \FreeCRM\Http\Vtiger_Request $request
	 */
	public function validateRequest(\FreeCRM\Http\Vtiger_Request $request)
	{
		$request->validateReadAccess();
	}
}
