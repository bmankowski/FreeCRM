<?php

namespace App\Modules\Settings\Mail\Actions;



/**
 * Mail Mass delete action model class
 * @package YetiForce.Settings.Action
 * @license licenses/License.html
 * @author Adrian Koń <a.kon@yetiforce.com>
 */

class MassDelete extends \App\Modules\Base\Actions\Mass
{

	/**
	 * Checking permission 
	 * @param \App\Http\Vtiger_Request $request
	 * @throws \App\Exceptions\NoPermittedForAdmin
	 */
	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		$currentUserModel = $request->getUser();
		if (!$currentUserModel->isAdmin()) {
			throw new \App\Exceptions\NoPermittedForAdmin('LBL_PERMISSION_DENIED');
		}
	}
	
	/**
	 * Process
	 * @param \App\Http\Vtiger_Request $request
	 */
	public function process(\App\Http\Vtiger_Request $request)
	{
		$recordIds = \App\Modules\Settings\Mail\Models\ListView::getRecordIdsFromRequest($request);
		foreach ($recordIds as $recordId) {
			$recordModel = \App\Modules\Settings\Mail\Models\Record::getInstance($recordId);
			if (!$recordModel) {
				continue;
			}
			$recordModel->delete();
		}
		$response = new \App\Http\Vtiger_Response();
		$response->setResult(['success' => true]);
		$response->emit();
	}
}
