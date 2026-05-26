<?php

namespace App\Modules\Calendar\Actions;

/* +***********************************************************************************************************************************
 * The contents of this file are subject to the YetiForce Public License Version 1.1 (the "License"); you may not use this file except
 * in compliance with the License.
 * Software distributed under the License is distributed on an "AS IS" basis, WITHOUT WARRANTY OF ANY KIND, either express or implied.
 * See the License for the specific language governing rights and limitations under the License.
 * The Original Code is YetiForce.
 * The Initial Developer of the Original Code is YetiForce. Portions created by YetiForce are Copyright (C) www.yetiforce.com. 
 * All Rights Reserved.
 * *********************************************************************************************************************************** */

class Calendar extends \App\Base\Controllers\BaseActionController
{

	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$userPrivilegesModel = \App\Modules\Users\Models\Privileges::getCurrentUserPrivilegesModel();
		$permission = $userPrivilegesModel->hasModulePermission($moduleName);

		if (!$permission) {
			throw new \App\Exceptions\NoPermitted('LBL_PERMISSION_DENIED');
		}
	}

	public function __construct()
	{
		parent::__construct();
		$this->exposeMethod('getEvents');
		$this->exposeMethod('updateEvent');
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$mode = $request->getMode();
		if (!empty($mode)) {
			echo $this->invokeExposedMethod($mode, $request);
		}
	}

	public function getEvents(\App\Http\Vtiger_Request $request)
	{
		$record = \App\Modules\Calendar\Models\Calendar::getCleanInstance();
		$record->set('user', $request->get('user'));
		$record->set('types', $request->get('types'));
		$record->set('time', $request->get('time'));
		if ($request->get('start') && $request->get('end')) {
			$record->set('start', $request->get('start'));
			$record->set('end', $request->get('end'));
		}
		if ($request->has('filters')) {
			$record->set('filters', $request->get('filters'));
		}
		if ($request->get('widget')) {
			$record->set('customFilter', $request->get('customFilter'));
			$entity = $record->getEntityCount();
		} else {
			$entity = $record->getEntity();
		}

		$response = new \App\Http\Vtiger_Response();
		$response->setResult($entity);
		$response->emit();
	}

	public function updateEvent(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$recordId = $request->get('id');
		$actionname = 'EditView';
		if (!\App\Security\Privilege::isPermitted($moduleName, $actionname, $recordId)) {
			$succes = false;
		} else {
			$delta = $request->get('delta');

			$start = \App\Fields\DateTimeField::convertToDBTimeZone($request->get('start'));
			$date_start = $start->format('Y-m-d');
			$time_start = $start->format('H:i:s');
			$succes = false;
			if (!empty($recordId)) {
				try {
					$recordModel = \App\Modules\Base\Models\Record::getInstanceById($recordId, $moduleName);
					$end = self::changeDateTime($recordModel->get('due_date') . ' ' . $recordModel->get('time_end'), $delta);
					$due_date = $end['date'];
					$time_end = $end['time'];
					$recordModel->set('id', $recordId);
					$recordModel->set('date_start', $date_start);
					$recordModel->set('due_date', $due_date);
					if ($request->get('allDay') == 'true') {
						$recordModel->set('allday', 1);
						$start = self::changeDateTime($recordModel->get('date_start') . ' ' . $recordModel->get('time_start'), $delta);
						$recordModel->set('date_start', $start['date']);
					} else {
						$recordModel->set('time_start', $time_start);
						$recordModel->set('time_end', $time_end);
						$recordModel->set('allday', 0);
					}
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
