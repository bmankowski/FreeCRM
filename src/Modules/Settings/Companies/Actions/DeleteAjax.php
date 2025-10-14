<?php

namespace FreeCRM\Modules\Settings\Companies\Actions;



/**
 * Companies delete action model class
 * @package YetiForce.Settings.Action
 * @license licenses/License.html
 * @author Adrian Koń <a.kon@yetiforce.com>
 */

use FreeCRM\Modules\Settings\Companies\Models\Record as Settings_Companies_Record_Model;
class DeleteAjax extends \FreeCRM\Modules\Settings\Vtiger\Actions\Delete
{

	/**
	 * Process
	 * @param \FreeCRM\Http\Vtiger_Request $request
	 */
	public function process(\FreeCRM\Http\Vtiger_Request $request)
	{
		$record = $request->get('record');
		$qualifiedModuleName = $request->getModule(false);
		$recordModel = Settings_Companies_Record_Model::getInstance($record);
		$recordModel->delete();

		$moduleModel = Settings_Vtiger_Module_Model::getInstance($qualifiedModuleName);
		header("Location: {$moduleModel->getDefaultUrl()}");
	}
	
	/**
	 * Validate Request
	 * @param \FreeCRM\Http\Vtiger_Request $request
	 */
	public function validateRequest(\FreeCRM\Http\Vtiger_Request $request)
	{
		$request->validateReadAccess();
	}
}
