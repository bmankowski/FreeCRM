<?php

namespace FreeCRM\Modules\Settings\PDF\Actions;



/**
 * Delete Action Class for PDF Settings
 * @package YetiForce.Action
 * @license licenses/License.html
 * @author Maciej Stencel <m.stencel@yetiforce.com>
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */

use FreeCRM\Modules\Settings\PDF\Models\Record as Settings_PDF_Record_Model;
class DeleteAjax extends \FreeCRM\Modules\Settings\Vtiger\Actions\Index
{

	public function process(\FreeCRM\Http\Vtiger_Request $request)
	{
		$recordId = $request->get('record');

		$response = new \FreeCRM\Http\Vtiger_Response();
		$recordModel = \FreeCRM\Modules\Vtiger\Models\PDF::getInstanceById($recordId);
		if (Settings_PDF_Record_Model::delete($recordModel)) {
			$response->setResult(array('success' => 'true'));
		} else {
			$response->setResult(array('success' => 'false'));
		}
		$response->emit();
	}
}
