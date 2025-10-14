<?php

namespace FreeCRM\Modules\Settings\MappedFields\Actions;



/**
 * Delete Action Class for MappedFields Settings
 * @package YetiForce.Action
 * @license licenses/License.html
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 */

use FreeCRM\Modules\Settings\MappedFields\Models\Module as Settings_MappedFields_Module_Model;
class DeleteAjax extends \FreeCRM\Modules\Settings\Vtiger\Actions\Index
{

	public function process(\FreeCRM\Http\Vtiger_Request $request)
	{
		$recordId = $request->get('record');

		$response = new \FreeCRM\Http\Vtiger_Response();
		$moduleInstance = Settings_MappedFields_Module_Model::getInstanceById($recordId);
		if ($moduleInstance->delete()) {
			$response->setResult(array('success' => 'true'));
		} else {
			$response->setResult(array('success' => 'false'));
		}
		$response->emit();
	}
}
