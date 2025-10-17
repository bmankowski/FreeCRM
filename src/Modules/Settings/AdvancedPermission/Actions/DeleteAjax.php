<?php

namespace App\Modules\Settings\AdvancedPermission\Actions;



/**
 * Advanced permission delete action model class
 * @package YetiForce.Settings.Action
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */

class DeleteAjax extends \App\Modules\Settings\Vtiger\Actions\Delete
{

	public function process(\App\Http\Vtiger_Request $request)
	{
		$record = $request->get('record');
		$qualifiedModuleName = $request->getModule(false);
		$recordModel = Settings_AdvancedPermission_Record_Model::getInstance($record);
		$recordModel->delete();

		$moduleModel = \App\Modules\Settings\Vtiger\Models\Module::getInstance($qualifiedModuleName);
		header("Location: {$moduleModel->getDefaultUrl()}");
	}

	public function validateRequest(\App\Http\Vtiger_Request $request)
	{
		$request->validateReadAccess();
	}
}
