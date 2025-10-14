<?php

namespace FreeCRM\Modules\Settings\WebserviceUsers\Actions;



/**
 * Class to delete
 * @package YetiForce.Settings.Action
 * @license licenses/License.html
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 */

use FreeCRM\Modules\Settings\WebserviceUsers\Models\Record as Settings_WebserviceUsers_Record_Model;
class DeleteAjax extends \FreeCRM\Modules\Settings\Vtiger\Actions\Delete
{

	/**
	 * Function  proccess
	 * @param \FreeCRM\Http\Vtiger_Request $request
	 */
	public function process(\FreeCRM\Http\Vtiger_Request $request)
	{
		$recordId = $request->get('record');
		$typeApi = $request->get('typeApi');
		$recordModel = Settings_WebserviceUsers_Record_Model::getInstanceById($recordId, $typeApi);
		$result = $recordModel->delete();

		$responceToEmit = new \FreeCRM\Http\Vtiger_Response();
		$responceToEmit->setResult($result);
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
