<?php

namespace App\Modules\Settings\PDF\Actions;



/**
 * Delete Action Class for PDF Settings
 * @package YetiForce.Action
 * @license licenses/License.html
 * @author Maciej Stencel <m.stencel@yetiforce.com>
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */

class DeleteAjax extends \App\Modules\Settings\Base\Actions\Index
{

	public function process(\App\Http\Vtiger_Request $request)
	{
		$recordId = $request->get('record');

		$response = new \App\Http\Vtiger_Response();
		$recordModel = \App\Modules\Base\Models\PDF::getInstanceById($recordId);
		if (\App\Modules\Settings\PDF\Models\Record::delete($recordModel)) {
			$response->setResult(array('success' => 'true'));
		} else {
			$response->setResult(array('success' => 'false'));
		}
		$response->emit();
	}
}
