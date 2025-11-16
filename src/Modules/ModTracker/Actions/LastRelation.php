<?php

namespace App\Modules\ModTracker\Actions;

/**
 * LastRelation Class
 * @package YetiForce.Action
 * @license licenses/License.html
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 */
class LastRelation extends \App\Base\Controllers\BaseActionController
{

	/**
	 * Checking permission
	 * @param \App\Http\Vtiger_Request $request
	 * @throws \App\Exceptions\NoPermittedToRecord
	 */
	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		$sourceModule = $request->get('sourceModule');
		$records = $request->get('recordsId');
		if (!empty($sourceModule)) {
			if (!in_array($sourceModule, \App\AppConfig::module('ModTracker', 'SHOW_TIMELINE_IN_LISTVIEW')) || !\App\Security\Privilege::isPermitted($sourceModule, 'TimeLineList')) {
				throw new \App\Exceptions\NoPermittedToRecord('LBL_PERMISSION_DENIED');
			}
			foreach ($records as $key => $recordId) {
				if (!\App\Security\Privilege::isPermitted($sourceModule, 'DetailView', $recordId)) {
					throw new \App\Exceptions\NoPermittedToRecord('LBL_PERMISSION_DENIED');
				}
			}
		} else {
			throw new \App\Exceptions\NoPermittedToRecord('LBL_PERMISSION_DENIED');
		}
	}

	/**
	 * Process
	 * @param \App\Http\Vtiger_Request $request
	 */
	public function process(\App\Http\Vtiger_Request $request)
	{
		$records = $request->get('recordsId');
		$result = \App\Modules\ModTracker\Models\Record::getLastRelation($records, $request->get('sourceModule'));
		$response = new \App\Http\Vtiger_Response();
		$response->setResult($result);
		$response->emit();
	}

	/**
	 * Validate request
	 * @param \App\Http\Vtiger_Request $request
	 * @return type
	 */
	public function validateRequest(\App\Http\Vtiger_Request $request)
	{
		return $request->validateWriteAccess();
	}
}
