<?php

namespace App\Modules\Settings\Companies\Actions;



/**
 * Companies delete action model class
 * @package YetiForce.Settings.Action
 * @license licenses/License.html
 * @author Adrian Koń <a.kon@yetiforce.com>
 */

class DeleteAjax extends \App\Modules\Settings\Vtiger\Actions\Delete
{

	/**
	 * Process
	 * @param \App\Http\Vtiger_Request $request
	 */
	public function process(\App\Http\Vtiger_Request $request)
	{
		$record = $request->get('record');
		$qualifiedModuleName = $request->getModule(false);
		$recordModel = \App\Modules\Settings\Companies\Models\Record::getInstance($record);
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
