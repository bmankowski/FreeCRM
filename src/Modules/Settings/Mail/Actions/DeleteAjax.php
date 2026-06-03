<?php

namespace App\Modules\Settings\Mail\Actions;



/**
 * Mail delete action model class
 * @package YetiForce.Settings.Action
 * @license licenses/License.html
 * @author Adrian Koń <a.kon@yetiforce.com>
 */

class DeleteAjax extends \App\Modules\Settings\Base\Actions\Delete
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
		$record = $request->get('record');
		$qualifiedModuleName = $request->getModule(false);
		$moduleModel = \App\Modules\Settings\Base\Models\Module::getInstance($qualifiedModuleName);
		$recordModel = \App\Modules\Settings\Mail\Models\Record::getInstance($record);
		if ($recordModel) {
			$recordModel->delete();
		}
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
