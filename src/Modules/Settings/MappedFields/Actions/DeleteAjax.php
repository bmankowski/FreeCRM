<?php

namespace App\Modules\Settings\MappedFields\Actions;



/**
 * Delete Action Class for MappedFields Settings
 * @package YetiForce.Action
 * @license licenses/License.html
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 */

class DeleteAjax extends \App\Modules\Settings\Vtiger\Actions\Index
{

	public function process(\App\Http\Vtiger_Request $request)
	{
		$recordId = $request->get('record');

		$response = new \App\Http\Vtiger_Response();
		$moduleInstance = \App\Modules\Settings\MappedFields\Models\Module::getInstanceById($recordId);
		if ($moduleInstance->delete()) {
			$response->setResult(array('success' => 'true'));
		} else {
			$response->setResult(array('success' => 'false'));
		}
		$response->emit();
	}
}
