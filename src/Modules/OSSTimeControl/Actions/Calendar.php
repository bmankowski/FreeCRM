<?php
/* {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} */


namespace App\Modules\OSSTimeControl\Actions;

class Calendar extends \App\Base\Controllers\BaseActionController
{

	public function __construct()
	{
		parent::__construct();
		$this->exposeMethod('getEvent');
		$this->exposeMethod('updateEvent');
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$mode = $request->getMode();
		if (!empty($mode)) {
			echo $this->invokeExposedMethod($mode, $request);
		}
	}

	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		$currentUserPriviligesModel = \App\Modules\Users\Models\Privileges::getCurrentUserPrivilegesModel();
		if (!$currentUserPriviligesModel->hasModulePermission($request->getModule())) {
			throw new \App\Exceptions\NoPermitted('LBL_PERMISSION_DENIED');
		}
	}

	public function getEvent(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$id = $request->get('id');

		$record = OSSTimeControl_Calendar_Model::getInstance();
		$record->set('user', $request->get('user'));
		$record->set('types', $request->get('types'));
		if ($request->get('start') && $request->get('end')) {
			$record->set('start', $request->get('start'));
			$record->set('end', $request->get('end'));
		}
		$entity = $record->getEntity();

		$response = new \App\Http\Vtiger_Response();
		$response->setResult($entity);
		$response->emit();
	}

	public function updateEvent(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$recordId = $request->get('id');
		$date_start = date('Y-m-d', strtotime($request->get('start')));
		$time_start = date('H:i:s', strtotime($request->get('start')));
		$succes = false;
		if (\App\Security\Privilege::isPermitted($moduleName, 'EditView', $recordId)) {
			if (!empty($recordId)) {
				try {
					$delta = $request->get('delta');
					$recordModel = \App\Modules\Base\Models\Record::getInstanceById($recordId, $moduleName);
					$end = self::changeDateTime($recordModel->get('due_date') . ' ' . $recordModel->get('time_end'), $delta);
					$due_date = $end['date'];
					$time_end = $end['time'];
					$recordModel->set('id', $recordId);
					$recordModel->set('date_start', $date_start);
					$recordModel->set('time_start', $time_start);
					$recordModel->set('due_date', $due_date);
					$recordModel->set('time_end', $time_end);
					$recordModel->save();
					$succes = true;
				} catch (Exception $e) {
					$succes = false;
				}
			}
		}
		$response = new \App\Http\Vtiger_Response();
		$response->setResult($succes);
		$response->emit();
	}

	public function changeDateTime($datetime, $delta)
	{
		$date = new \DateTime($datetime);
		if ($delta['days'] != 0) {
			$date = $date->modify('+' . $delta['days'] . ' days');
		}
		if ($delta['hours'] != 0) {
			$date = $date->modify('+' . $delta['hours'] . ' hours');
		}
		if ($delta['minutes'] != 0) {
			$date = $date->modify('+' . $delta['minutes'] . ' minutes');
		}
		return ['date' => $date->format('Y-m-d'), 'time' => $date->format('H:i:s')];
	}
}
