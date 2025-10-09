<?php

namespace FreeCRM\Modules\ModTracker\Actions;

/**
 * LastRelation Class
 * @package YetiForce.Action
 * @license licenses/License.html
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 */
class LastRelation extends \FreeCRM\Runtime\Vtiger_Action_Controller
{

	/**
	 * Checking permission
	 * @param Vtiger_Request $request
	 * @throws \Exception\NoPermittedToRecord
	 */
	public function checkPermission(\FreeCRM\Http\Vtiger_Request $request)
	{
		$sourceModule = $request->get('sourceModule');
		$records = $request->get('recordsId');
		if (!empty($sourceModule)) {
			if (!in_array($sourceModule, \FreeCRM\AppConfig::module('ModTracker', 'SHOW_TIMELINE_IN_LISTVIEW')) || !\App\Privilege::isPermitted($sourceModule, 'TimeLineList')) {
				throw new \Exception\NoPermittedToRecord('LBL_PERMISSION_DENIED');
			}
			foreach ($records as $key => $recordId) {
				if (!App\Privilege::isPermitted($sourceModule, 'DetailView', $recordId)) {
					throw new \Exception\NoPermittedToRecord('LBL_PERMISSION_DENIED');
				}
			}
		} else {
			throw new \Exception\NoPermittedToRecord('LBL_PERMISSION_DENIED');
		}
	}

	/**
	 * Process
	 * @param Vtiger_Request $request
	 */
	public function process(Vtiger_Request $request)
	{
		$records = $request->get('recordsId');
		$result = ModTracker_Record_Model::getLastRelation($records, $request->get('sourceModule'));
		$response = new Vtiger_Response();
		$response->setResult($result);
		$response->emit();
	}

	/**
	 * Validate request
	 * @param Vtiger_Request $request
	 * @return type
	 */
	public function validateRequest(Vtiger_Request $request)
	{
		return $request->validateWriteAccess();
	}
}
