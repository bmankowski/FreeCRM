<?php

namespace FreeCRM\Modules\Settings\AutomaticAssignment\Actions;
use FreeCRM\Modules\Settings\Vtiger\Models\Tracker;



/**
 * Automatic assignment save action model class
 * @package YetiForce.Settings.Action
 * @license licenses/License.html
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 */

use FreeCRM\Modules\Settings\AutomaticAssignment\Models\Record as Settings_AutomaticAssignment_Record_Model;
class SaveAjax extends \FreeCRM\Modules\Settings\Vtiger\Actions\Save
{

	/**
	 * Constructor
	 */
	public function __construct()
	{
		\FreeCRM\Modules\Settings\Vtiger\Models\Tracker::lockTracking();
		parent::__construct();
		$this->exposeMethod('save');
		$this->exposeMethod('deleteElement');
		$this->exposeMethod('changeRoleType');
	}

	/**
	 * Save
	 * @param \FreeCRM\Http\Vtiger_Request $request
	 */
	public function save(\FreeCRM\Http\Vtiger_Request $request)
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

		$responceToEmit = new \FreeCRM\Http\Vtiger_Response();
		$responceToEmit->setResult($recordModel->getId());
		$responceToEmit->emit();
	}

	/**
	 * Function changes the type of a given role
	 * @param \FreeCRM\Http\Vtiger_Request $request
	 */
	public function changeRoleType(\FreeCRM\Http\Vtiger_Request $request)
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

		$responceToEmit = new \FreeCRM\Http\Vtiger_Response();
		$responceToEmit->setResult($recordModel->getId());
		$responceToEmit->emit();
	}

	/**
	 * Function removes given value from record
	 * @param \FreeCRM\Http\Vtiger_Request $request
	 */
	public function deleteElement(\FreeCRM\Http\Vtiger_Request $request)
	{
		$member = $request->get('param');
		$qualifiedModuleName = $request->getModule(false);
		$recordId = $request->get('record');
		$recordModel = Settings_AutomaticAssignment_Record_Model::getInstanceById($recordId);
		$recordModel->deleteElement($request->get('name'), $request->get('value'));

		$responceToEmit = new \FreeCRM\Http\Vtiger_Response();
		$responceToEmit->setResult($recordModel->getId());
		$responceToEmit->emit();
	}
}
