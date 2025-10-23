<?php

namespace App\Modules\Settings\Mail\Actions;



/**
 * Mail Mass accept action model class
 * @package YetiForce.Settings.Action
 * @license licenses/License.html
 * @author Adrian Koń <a.kon@yetiforce.com>
 */
class MassAccept extends \App\Modules\Vtiger\Actions\Mass
{

	/**
	 * Checking permission 
	 * @param \App\Http\Vtiger_Request $request
	 * @throws \Exception\NoPermittedForAdmin
	 */
	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		$currentUserModel = \App\User::getCurrentUserModel();
		if (!$currentUserModel->isAdmin()) {
			throw new \Exception\NoPermittedForAdmin('LBL_PERMISSION_DENIED');
		}
	}
	
	/**
	 * Process
	 * @param \App\Http\Vtiger_Request $request
	 */
	public function process(\App\Http\Vtiger_Request $request)
	{

		$selectedIds = $request->get('selected_ids');

		$recordIds = $this->getRecordsListFromRequest($request);
		
		foreach ($recordIds as $recordId) {
			\App\Modules\Settings\Mail\Models\Config::acceptanceRecord($recordId);
		}
		$response = new \App\Http\Vtiger_Response();
		$response->setResult(['success' => true]);
		$response->emit();
	}
}
