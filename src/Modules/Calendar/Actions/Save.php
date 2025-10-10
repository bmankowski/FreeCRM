<?php

namespace FreeCRM\Modules\Calendar\Actions;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

class Save extends \FreeCRM\Runtime\Vtiger_Action_Controller
{

	public function process(\FreeCRM\Http\Vtiger_Request $request)
	{
		$recordModel = $this->saveRecord($request);
		$loadUrl = $recordModel->getDetailViewUrl();

		if ($request->get('relationOperation')) {
			$parentModuleName = $request->get('sourceModule');
			$parentRecordId = $request->get('sourceRecord');
			$parentRecordModel = \FreeCRM\Modules\Vtiger\Models\Record::getInstanceById($parentRecordId, $parentModuleName);
			$loadUrl = $parentRecordModel->getDetailViewUrl();
		} else if ($request->get('returnToList')) {
			$moduleModel = $recordModel->getModule();
			$loadUrl = $moduleModel->getListViewUrl();
		}
		header("Location: $loadUrl");
	}

	/**
	 * Function to save record
	 * @param Vtiger_Request $request - values of the record
	 * @return \FreeCRM\Modules\Vtiger\Models\Record - record Model of saved record
	 */
	public function saveRecord(\FreeCRM\Http\Vtiger_Request $request)
	{
		$recordModel = $this->getRecordModelFromRequest($request);
		$recordModel->save();
		if ($request->get('relationOperation')) {
			$parentModuleName = $request->get('sourceModule');
			$parentModuleModel = \FreeCRM\Modules\Vtiger\Models\Module::getInstance($parentModuleName);
			$parentRecordId = $request->get('sourceRecord');
			$relatedModule = $recordModel->getModule();
			if ($relatedModule->getName() == 'Events') {
				$relatedModule = \FreeCRM\Modules\Vtiger\Models\Module::getInstance('Calendar');
			}
			$relatedRecordId = $recordModel->getId();

			$relationModel = \FreeCRM\Modules\Vtiger\Models\Relation::getInstance($parentModuleModel, $relatedModule);
			$relationModel->addRelation($parentRecordId, $relatedRecordId);
		}
		return $recordModel;
	}

	/**
	 * Function to get the record model based on the request parameters
	 * @param Vtiger_Request $request
	 * @return \FreeCRM\Modules\Vtiger\Models\Record or Module specific Record Model instance
	 */
	protected function getRecordModelFromRequest(\FreeCRM\Http\Vtiger_Request $request)
	{
		$recordModel = parent::getRecordModelFromRequest($request);
		//Start Date and Time values
		$startTime = Vtiger_Time_UIType::getTimeValueWithSeconds($request->get('time_start'));
		$startDate = \FreeCRM\Modules\Vtiger\UiTypes\Date::getDBInsertedValue($request->get('date_start'));
		if ($startTime) {
			$startTime = Vtiger_Time_UIType::getTimeValueWithSeconds($startTime);
			$startDateTime = Vtiger_Datetime_UIType::getDBDateTimeValue($request->get('date_start') . ' ' . $startTime);
			list($startDate, $startTime) = explode(' ', $startDateTime);
		}
		$recordModel->set('date_start', $startDate);
		$recordModel->set('time_start', $startTime);
		//End Date and Time values
		$endTime = $request->get('time_end');
		$endDate = \FreeCRM\Modules\Vtiger\UiTypes\Date::getDBInsertedValue($request->get('due_date'));
		if ($endTime) {
			$endTime = Vtiger_Time_UIType::getTimeValueWithSeconds($endTime);
			$endDateTime = Vtiger_Datetime_UIType::getDBDateTimeValue($request->get('due_date') . " " . $endTime);
			list($endDate, $endTime) = explode(' ', $endDateTime);
		}
		$recordModel->set('time_end', $endTime);
		$recordModel->set('due_date', $endDate);
		$activityType = $request->get('activitytype');
		if (empty($activityType)) {
			$recordModel->set('activitytype', 'Task');
			$recordModel->set('visibility', 'Private');
		}
		if ($request->has('saveAndClose')) {
			$recordModel->set('activitystatus', $request->get('saveAndClose'));
		}
		//Due to dependencies on the older code
		if ($request->get('set_reminder') && $request->get('set_reminder') !== 'No') {
			unset($_SESSION['next_reminder_time']);
			$remDays = (int) $request->get('remdays');
			$remHrs = (int) $request->get('remhrs');
			$remMin = (int) $request->get('remmin');
			$reminderTime = $remDays * 24 * 60 + $remHrs * 60 + $remMin;
			$recordModel->set('set_reminder', $reminderTime);
		} else {
			$recordModel->set('set_reminder', false);
		}
		$time = (strtotime($request->get('time_end'))) - (strtotime($request->get('time_start')));
		$diffinSec = (strtotime($request->get('due_date'))) - (strtotime($request->get('date_start')));
		$diff_days = floor($diffinSec / (60 * 60 * 24));
		$hours = ((float) $time / 3600) + ($diff_days * 24);
		$minutes = ((float) $hours - (int) $hours) * 60;
		$recordModel->set('duration_hours', (int) $hours);
		$recordModel->set('duration_minutes', round($minutes, 0));
		return $recordModel;
	}
}
