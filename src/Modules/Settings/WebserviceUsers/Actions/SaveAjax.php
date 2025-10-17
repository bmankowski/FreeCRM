<?php

namespace App\Modules\Settings\WebserviceUsers\Actions;



/**
 * Save Application
 * @package YetiForce.Settings.Action
 * @license licenses/License.html
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 */

use App\Modules\Settings\WebserviceUsers\Models\Record as Settings_WebserviceUsers_Record_Model;
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
			$recordModel = Settings_WebserviceUsers_Record_Model::getInstanceById($recordId, $typeApi);
		} else {
			$recordModel = Settings_WebserviceUsers_Record_Model::getCleanInstance($typeApi);
		}
		$result = $recordModel->save($data);

		$responceToEmit = new \App\Http\Vtiger_Response();
		$responceToEmit->setResult($result);
		$responceToEmit->emit();
	}
}
