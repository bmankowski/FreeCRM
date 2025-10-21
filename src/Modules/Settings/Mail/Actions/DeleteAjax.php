<?php

namespace App\Modules\Settings\Mail\Actions;



/**
 * Mail delete action model class
 * @package YetiForce.Settings.Action
 * @license licenses/License.html
 * @author Adrian Koń <a.kon@yetiforce.com>
 */

class DeleteAjax extends \App\Modules\Settings\Vtiger\Actions\Delete
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
		$record = $request->get('record');
		$qualifiedModuleName = $request->getModule(false);
		$recordModel = \App\Modules\Settings\Mail\Models\Record::getInstance($record);
		$recordModel->delete();

		$moduleModel = \App\Modules\Settings\Vtiger\Models\Module::getInstance($qualifiedModuleName);
		header("Location: {$moduleModel->getDefaultUrl()}");
	}
	
	/**
	 * Validate Request
	 * @param \App\Http\Vtiger_Request $request
	 */
	public function validateRequest(\App\Http\Vtiger_Request $request)
	{
		$request->validateReadAccess();
	}
}
