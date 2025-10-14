<?php

namespace FreeCRM\Modules\Settings\Mail\Actions;



/**
 * Mail Mass accept action model class
 * @package YetiForce.Settings.Action
 * @license licenses/License.html
 * @author Adrian Koń <a.kon@yetiforce.com>
 */
class MassAccept extends \Vtiger_Mass_Action
{

	/**
	 * Checking permission 
	 * @param \FreeCRM\Http\Vtiger_Request $request
	 * @throws \Exception\NoPermittedForAdmin
	 */
	public function checkPermission(\FreeCRM\Http\Vtiger_Request $request)
	{
		$currentUserModel = \App\User::getCurrentUserModel();
		if (!$currentUserModel->isAdmin()) {
			throw new \Exception\NoPermittedForAdmin('LBL_PERMISSION_DENIED');
		}
	}
	
	/**
	 * Process
	 * @param \FreeCRM\Http\Vtiger_Request $request
	 */
	public function process(\FreeCRM\Http\Vtiger_Request $request)
	{

		$selectedIds = $request->get('selected_ids');

		$recordIds = $this->getRecordsListFromRequest($request);
		
		foreach ($recordIds as $recordId) {
			\FreeCRM\Modules\Settings\Mail\Models\Config::acceptanceRecord($recordId);
		}
		$response = new \FreeCRM\Http\Vtiger_Response();
		$response->setResult(['success' => true]);
		$response->emit();
	}
}
