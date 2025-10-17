<?php

namespace App\Modules\Settings\AutomaticAssignment\Actions;
use App\Modules\Settings\Vtiger\Models\Tracker;



/**
 * Automatic assignment save action model class
 * @package YetiForce.Settings.Action
 * @license licenses/License.html
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 */

class SaveAjax extends \App\Modules\Settings\Vtiger\Actions\Save
{

	/**
	 * Constructor
	 */
	public function __construct()
	{
		\App\Modules\Settings\Vtiger\Models\Tracker::lockTracking();
		parent::__construct();
		$this->exposeMethod('save');
		$this->exposeMethod('deleteElement');
		$this->exposeMethod('changeRoleType');
	}

	/**
	 * Save
	 * @param \App\Http\Vtiger_Request $request
	 */
	public function save(\App\Http\Vtiger_Request $request)
	{
		$data = $request->get('param');
		$recordId = $request->get('record');
		if ($recordId) {
			$recordModel = Settings_AutomaticAssignment_Record_Model::getInstanceById($recordId);
		} else {
			$recordModel = Settings_AutomaticAssignment_Record_Model::getCleanInstance();
		}

		$dataFull = array_merge($recordModel->getData(), $data);
		$recordModel->setData($dataFull);
		$recordModel->checkDuplicate = true;
		$recordModel->save();

		$responceToEmit = new \App\Http\Vtiger_Response();
		$responceToEmit->setResult($recordModel->getId());
		$responceToEmit->emit();
	}

	/**
	 * Function changes the type of a given role
	 * @param \App\Http\Vtiger_Request $request
	 */
	public function changeRoleType(\App\Http\Vtiger_Request $request)
	{
		$member = $request->get('param');
		$qualifiedModuleName = $request->getModule(false);
		$recordId = $request->get('record');
		if ($recordId) {
			$recordModel = Settings_AutomaticAssignment_Record_Model::getInstanceById($recordId);
		} else {
			$recordModel = Settings_AutomaticAssignment_Record_Model::getCleanInstance();
		}
		$recordModel->changeRoleType($member);

		$responceToEmit = new \App\Http\Vtiger_Response();
		$responceToEmit->setResult($recordModel->getId());
		$responceToEmit->emit();
	}

	/**
	 * Function removes given value from record
	 * @param \App\Http\Vtiger_Request $request
	 */
	public function deleteElement(\App\Http\Vtiger_Request $request)
	{
		$member = $request->get('param');
		$qualifiedModuleName = $request->getModule(false);
		$recordId = $request->get('record');
		$recordModel = Settings_AutomaticAssignment_Record_Model::getInstanceById($recordId);
		$recordModel->deleteElement($request->get('name'), $request->get('value'));

		$responceToEmit = new \App\Http\Vtiger_Response();
		$responceToEmit->setResult($recordModel->getId());
		$responceToEmit->emit();
	}
}
