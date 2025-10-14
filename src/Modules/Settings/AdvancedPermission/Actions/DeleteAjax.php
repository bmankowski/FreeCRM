<?php

namespace FreeCRM\Modules\Settings\AdvancedPermission\Actions;



/**
 * Advanced permission delete action model class
 * @package YetiForce.Settings.Action
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */

use FreeCRM\Modules\Settings\AdvancedPermission\Models\Record as Settings_AdvancedPermission_Record_Model;
class DeleteAjax extends \FreeCRM\Modules\Settings\Vtiger\Actions\Delete
{

	public function process(\FreeCRM\Http\Vtiger_Request $request)
	{
		$record = $request->get('record');
		$qualifiedModuleName = $request->getModule(false);
		$recordModel = Settings_AdvancedPermission_Record_Model::getInstance($record);
		$recordModel->delete();

		$moduleModel = Settings_Vtiger_Module_Model::getInstance($qualifiedModuleName);
		header("Location: {$moduleModel->getDefaultUrl()}");
	}

	public function validateRequest(\FreeCRM\Http\Vtiger_Request $request)
	{
		$request->validateReadAccess();
	}
}
